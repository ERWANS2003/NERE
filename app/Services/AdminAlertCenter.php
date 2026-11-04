<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;

class AdminAlertCenter
{
    /** @return array<int, array<string, mixed>> */
    public function alerts(): array
    {
        $health = app(SystemHealth::class);
        $report = $health->report();
        $alerts = [];

        $links = [
            'scheduler' => ['route' => 'admin.health', 'anchor' => 'sante-taches', 'severity' => 'critical'],
            'queue' => ['route' => 'admin.health', 'anchor' => 'sante-echecs', 'severity' => 'critical'],
            'mail' => ['route' => 'admin.health', 'anchor' => 'sante-mail', 'severity' => 'warning'],
            'ldap' => ['route' => 'admin.settings', 'anchor' => 'ldap', 'severity' => 'critical'],
            'backup' => ['route' => 'admin.health', 'anchor' => 'sante-sauvegarde', 'severity' => 'critical'],
            'disk' => ['route' => 'admin.health', 'anchor' => 'sante-disque', 'severity' => 'critical'],
        ];

        foreach ($health->problems($report) as $key => $message) {
            $link = $links[$key];
            $alerts[] = [
                'key' => $key,
                'title' => ucfirst($key),
                'message' => $message,
                'severity' => $link['severity'],
                'url' => route($link['route']).'#'.$link['anchor'],
            ];
        }

        $slaBreaches = Ticket::query()
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->where(fn ($query) => $query->whereNotNull('response_breached_at')->orWhereNotNull('resolution_breached_at'))
            ->count();

        if ($slaBreaches > 0) {
            $alerts[] = [
                'key' => 'sla',
                'title' => 'SLA support',
                'message' => "$slaBreaches ticket(s) ouvert(s) ont dépassé au moins un objectif SLA.",
                'severity' => 'warning',
                'url' => route('admin.tickets.index'),
            ];
        }

        return $alerts;
    }
}
