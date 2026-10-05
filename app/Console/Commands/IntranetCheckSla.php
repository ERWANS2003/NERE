<?php

namespace App\Console\Commands;

use App\Models\Intranet\Submission;
use App\Notifications\Intranet\SubmissionSlaBreach;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Vérifie toutes les heures les demandes dont le SLA est à 75% ou dépassé.
 * Planifié dans bootstrap/app.php : ->hourly()
 */
class IntranetCheckSla extends Command
{
    protected $signature   = 'intranet:check-sla';
    protected $description = 'Vérifie les SLA des demandes intranet et envoie les alertes';

    public function handle(): int
    {
        $now       = now();
        $breached  = 0;
        $warned    = 0;

        Submission::open()
            ->whereNotNull('due_at')
            ->with(['requester', 'assignee', 'form.service.department'])
            ->chunkById(100, function ($submissions) use ($now, &$breached, &$warned) {
                foreach ($submissions as $sub) {
                    if (! $sub->due_at) continue;

                    $totalSeconds   = $sub->created_at->diffInSeconds($sub->due_at);
                    $elapsedSeconds = $sub->created_at->diffInSeconds($now);
                    $ratio          = $totalSeconds > 0 ? $elapsedSeconds / $totalSeconds : 1;

                    // Destinataires : demandeur + assigné si présent
                    $recipients = collect([$sub->requester]);
                    if ($sub->assignee) $recipients->push($sub->assignee);
                    $recipients = $recipients->unique('id')->filter();

                    if ($ratio >= 1.0 && ! $sub->isTerminal()) {
                        // SLA dépassé
                        Notification::send($recipients, new SubmissionSlaBreach($sub, false));
                        $sub->update(['status' => Submission::STATUS_ESCALATED]);
                        $breached++;
                    } elseif ($ratio >= 0.75) {
                        // Avertissement 75%
                        Notification::send($recipients, new SubmissionSlaBreach($sub, true));
                        $warned++;
                    }
                }
            });

        $this->info("✅ SLA vérifiés — {$breached} dépassé(s), {$warned} avertissement(s) envoyé(s).");

        return self::SUCCESS;
    }
}
