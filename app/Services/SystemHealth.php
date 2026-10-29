<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Vérifications techniques affichées sur la page « Santé du serveur » et
 * utilisées par l'alerte envoyée aux administrateurs (intranet:alerte-sante).
 */
class SystemHealth
{
    public const SCHEDULER_HEARTBEAT_KEY = 'sante:scheduler-last-run';

    public const BACKUP_STATUS_FILE = 'sauvegarde-statut.json';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function report(): array
    {
        $schedulerLastRun = Cache::get(self::SCHEDULER_HEARTBEAT_KEY);
        $schedulerLastRun = $schedulerLastRun ? Carbon::parse($schedulerLastRun) : null;

        $oldestJob = DB::table('jobs')->min('created_at');
        $oldestJob = $oldestJob ? Carbon::createFromTimestamp($oldestJob) : null;
        $failedTotal = DB::table('failed_jobs')->count();

        $mailHost = (string) config('mail.mailers.smtp.host');
        $mailFrom = (string) config('mail.from.address');

        $backupFile = storage_path('app/'.self::BACKUP_STATUS_FILE);
        $backup = is_file($backupFile) ? json_decode((string) file_get_contents($backupFile), true) : null;

        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());

        $ldapError = AppSetting::value('ldap_last_sync_error');

        return [
            'scheduler' => [
                'last_run' => $schedulerLastRun,
                'ok' => $schedulerLastRun !== null && $schedulerLastRun->gt(now()->subMinutes(5)),
            ],
            'queue' => [
                'pending' => DB::table('jobs')->count(),
                'oldest' => $oldestJob,
                'failed_total' => $failedTotal,
                'ok' => $failedTotal === 0 && ($oldestJob === null || $oldestJob->gt(now()->subMinutes(10))),
            ],
            'mail' => [
                'mailer' => config('mail.default'),
                'host' => $mailHost,
                'port' => config('mail.mailers.smtp.port'),
                'from' => $mailFrom,
                'ok' => config('mail.default') !== 'smtp'
                    || (! in_array($mailHost, ['127.0.0.1', 'localhost', ''], true) && ! str_ends_with($mailFrom, '@example.com')),
            ],
            'ldap' => [
                'enabled' => (bool) config('ldap_integration.enabled'),
                'frequency' => AppSetting::value('ldap_sync_frequency', 'manual'),
                'last_sync' => AppSetting::value('ldap_last_sync_at'),
                'last_error' => $ldapError,
                'ok' => ! config('ldap_integration.enabled') || blank($ldapError),
            ],
            'backup' => [
                'status' => $backup,
                'ok' => isset($backup['ok'], $backup['date'])
                    && $backup['ok'] === true
                    && Carbon::parse($backup['date'])->gt(now()->subHours(36)),
            ],
            'disk' => [
                'free' => $diskFree ?: null,
                'total' => $diskTotal ?: null,
                'ok' => $diskFree && $diskTotal ? $diskFree / $diskTotal > 0.10 : true,
            ],
        ];
    }

    /**
     * Problèmes en cours, par clé de vérification.
     *
     * @param  array<string, array<string, mixed>>|null  $report
     * @return array<string, string>
     */
    public function problems(?array $report = null): array
    {
        $report ??= $this->report();
        $labels = [
            'scheduler' => 'Les tâches planifiées ne tournent plus.',
            'queue' => 'Des envois sont en échec ou bloqués dans la file d’attente.',
            'mail' => 'L’envoi des e-mails n’est pas configuré.',
            'ldap' => 'La dernière synchronisation Active Directory a échoué.',
            'backup' => 'Aucune sauvegarde réussie depuis plus de 36 h.',
            'disk' => 'Moins de 10 % d’espace disque libre.',
        ];

        return collect($labels)
            ->filter(fn (string $label, string $key) => ! $report[$key]['ok'])
            ->all();
    }
}
