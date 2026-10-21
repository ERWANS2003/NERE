<?php

namespace App\Services;

use App\Enums\DepartmentRole;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketSlaNotification;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TicketSlaService
{
    public function __construct(private readonly TicketSlaCalculator $calculator) {}

    /** @return array<string, CarbonInterface> */
    public function deadlinesFor(Ticket $ticket): array
    {
        $priority = $ticket->priority instanceof TicketPriority
            ? $ticket->priority->value
            : (string) $ticket->priority;
        $targets = config('intranet.sla.targets.'.$priority);

        if (! is_array($targets)) {
            throw new \InvalidArgumentException("Aucun objectif SLA n'est configuré pour la priorité [{$priority}].");
        }

        $startedAt = $ticket->created_at;

        if (! $startedAt) {
            throw new \LogicException('Le ticket doit être créé avant le calcul de ses échéances SLA.');
        }

        $responseMinutes = (int) $targets['response_minutes'];
        $resolutionMinutes = (int) $targets['resolution_minutes'];
        if ($responseMinutes < 1 || $resolutionMinutes < 1) {
            throw new \InvalidArgumentException("Les objectifs SLA de la priorité [{$priority}] doivent être positifs.");
        }

        return [
            'first_response_due_at' => $this->calculator->addBusinessMinutes($startedAt, $responseMinutes),
            'response_warning_at' => $this->calculator->warningAt($startedAt, $responseMinutes),
            'resolution_due_at' => $this->calculator->addBusinessMinutes($startedAt, $resolutionMinutes),
            'resolution_warning_at' => $this->calculator->warningAt($startedAt, $resolutionMinutes),
        ];
    }

    public function setDeadlines(Ticket $ticket, bool $resetAlerts = false): void
    {
        $ticket->forceFill($this->deadlinesFor($ticket));

        if ($resetAlerts) {
            $ticket->forceFill([
                'response_warning_sent_at' => null,
                'response_breached_at' => null,
                'resolution_warning_sent_at' => null,
                'resolution_breached_at' => null,
            ]);
        }

        $ticket->save();
    }

    public function restartResolutionDeadline(Ticket $ticket): void
    {
        $priority = $ticket->priority instanceof TicketPriority
            ? $ticket->priority->value
            : (string) $ticket->priority;
        $resolutionMinutes = (int) config("intranet.sla.targets.{$priority}.resolution_minutes");
        if ($resolutionMinutes < 1) {
            throw new \InvalidArgumentException("L'objectif SLA de résolution de la priorité [{$priority}] doit être positif.");
        }

        $startedAt = now(config('intranet.sla.timezone'));
        $ticket->forceFill([
            'resolution_due_at' => $this->calculator->addBusinessMinutes($startedAt, $resolutionMinutes),
            'resolution_warning_at' => $this->calculator->warningAt($startedAt, $resolutionMinutes),
            'resolution_warning_sent_at' => null,
            'resolution_breached_at' => null,
        ])->save();
    }

    public function checkDueAlerts(): int
    {
        $now = now(config('intranet.sla.timezone'));
        $ticketIds = Ticket::query()
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->where(function ($query) use ($now) {
                $query->where(function ($response) use ($now) {
                    $response->whereNull('first_responded_at')
                        ->whereNull('response_warning_sent_at')
                        ->where('response_warning_at', '<=', $now);
                })->orWhere(function ($response) use ($now) {
                    $response->whereNull('first_responded_at')
                        ->whereNull('response_breached_at')
                        ->where('first_response_due_at', '<=', $now);
                })->orWhere(function ($resolution) use ($now) {
                    $resolution->whereNull('resolution_warning_sent_at')
                        ->whereNull('resolved_at')
                        ->whereNull('closed_at')
                        ->where('resolution_warning_at', '<=', $now);
                })->orWhere(function ($resolution) use ($now) {
                    $resolution->whereNull('resolution_breached_at')
                        ->whereNull('resolved_at')
                        ->whereNull('closed_at')
                        ->where('resolution_due_at', '<=', $now);
                })->orWhereNull('first_response_due_at')
                    ->orWhereNull('resolution_due_at');
            })
            ->pluck('id');

        $alertsSent = 0;

        foreach ($ticketIds as $ticketId) {
            $alertsSent += DB::transaction(function () use ($ticketId, $now): int {
                $ticket = Ticket::query()->lockForUpdate()->find($ticketId);

                if (! $ticket || in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
                    return 0;
                }

                if (! $ticket->first_response_due_at || ! $ticket->resolution_due_at) {
                    $this->setDeadlines($ticket);
                }

                $alerts = [];

                if (! $ticket->first_responded_at) {
                    $this->recordAlert($ticket, 'response', $now, $alerts);
                }

                if (! $ticket->resolved_at && ! $ticket->closed_at) {
                    $this->recordAlert($ticket, 'resolution', $now, $alerts);
                }

                if ($alerts === []) {
                    return 0;
                }

                $recipients = $this->recipientsFor($ticket);
                if ($recipients->isEmpty()) {
                    throw new \RuntimeException("Aucun technicien IT actif ne peut recevoir les alertes du ticket [{$ticket->number}].");
                }

                $ticket->save();

                foreach ($alerts as [$metric, $stage]) {
                    Notification::send(
                        $recipients,
                        new TicketSlaNotification($ticket, $metric, $stage)
                    );
                }

                return count($alerts);
            });
        }

        return $alertsSent;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $alerts
     */
    private function recordAlert(Ticket $ticket, string $metric, CarbonInterface $now, array &$alerts): void
    {
        $dueColumn = $metric === 'response' ? 'first_response_due_at' : 'resolution_due_at';
        $warningAtColumn = $metric.'_warning_at';
        $warningSentColumn = $metric.'_warning_sent_at';
        $breachedColumn = $metric.'_breached_at';

        if (! $ticket->{$warningSentColumn}
            && ! $ticket->{$breachedColumn}
            && $ticket->{$warningAtColumn}
            && $ticket->{$dueColumn}
            && $ticket->{$warningAtColumn}->lte($now)
            && $ticket->{$dueColumn}->gt($now)) {
            $ticket->{$warningSentColumn} = $now;
            $alerts[] = [$metric, 'warning'];
        }

        if (! $ticket->{$breachedColumn} && $ticket->{$dueColumn} && $ticket->{$dueColumn}->lte($now)) {
            $ticket->{$breachedColumn} = $now;
            $alerts[] = [$metric, 'breached'];
        }
    }

    /** @return Collection<int, User> */
    private function recipientsFor(Ticket $ticket): Collection
    {
        $departmentId = $ticket->department_id;

        return User::query()
            ->where('actif', true)
            ->where(function ($query) use ($ticket, $departmentId) {
                if ($ticket->assignee_id) {
                    $query->whereKey($ticket->assignee_id);
                }

                $query->orWhereHas('departments', fn ($departments) => $departments
                    ->where('departments.id', $departmentId)
                    ->whereIn('department_user.role', [
                        DepartmentRole::Technician->value,
                        DepartmentRole::Director->value,
                    ]));
            })
            ->get();
    }
}
