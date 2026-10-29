<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * État technique du serveur : tâches planifiées, file d'attente, e-mail,
 * synchronisation AD, sauvegardes et espace disque.
 */
class SystemHealthController extends Controller
{
    public const SCHEDULER_HEARTBEAT_KEY = 'sante:scheduler-last-run';

    public const BACKUP_STATUS_FILE = 'sauvegarde-statut.json';

    public function index(): View
    {
        $schedulerLastRun = Cache::get(self::SCHEDULER_HEARTBEAT_KEY);
        $schedulerLastRun = $schedulerLastRun ? Carbon::parse($schedulerLastRun) : null;

        $oldestJob = DB::table('jobs')->min('created_at');
        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->limit(50)->get()
            ->map(fn ($job) => [
                'uuid' => $job->uuid,
                'failed_at' => Carbon::parse($job->failed_at),
                'name' => json_decode($job->payload, true)['displayName'] ?? '?',
                'error' => str($job->exception)->before("\n")->limit(200)->toString(),
            ]);

        $mailHost = (string) config('mail.mailers.smtp.host');
        $mailFrom = (string) config('mail.from.address');

        $backupFile = storage_path('app/'.self::BACKUP_STATUS_FILE);
        $backup = is_file($backupFile) ? json_decode((string) file_get_contents($backupFile), true) : null;

        $diskFree = @disk_free_space(base_path());
        $diskTotal = @disk_total_space(base_path());

        return view('admin.health.index', [
            'scheduler' => [
                'last_run' => $schedulerLastRun,
                'ok' => $schedulerLastRun !== null && $schedulerLastRun->gt(now()->subMinutes(5)),
            ],
            'queue' => [
                'pending' => DB::table('jobs')->count(),
                'oldest' => $oldestJob ? Carbon::createFromTimestamp($oldestJob) : null,
                'failed' => $failedJobs,
                'failed_total' => DB::table('failed_jobs')->count(),
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
                'last_error' => AppSetting::value('ldap_last_sync_error'),
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
        ]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        abort_unless(DB::table('failed_jobs')->where('uuid', $uuid)->exists(), 404);
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        return back()->with('success', 'Le job est remis dans la file d’attente.');
    }

    public function retryAll(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Tous les jobs en échec sont remis dans la file d’attente.');
    }

    public function forget(string $uuid): RedirectResponse
    {
        abort_unless(DB::table('failed_jobs')->where('uuid', $uuid)->exists(), 404);
        Artisan::call('queue:forget', ['id' => $uuid]);

        return back()->with('success', 'Le job en échec est supprimé.');
    }
}
