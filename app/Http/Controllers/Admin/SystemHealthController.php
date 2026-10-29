<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * État technique du serveur : tâches planifiées, file d'attente, e-mail,
 * synchronisation AD, sauvegardes et espace disque.
 */
class SystemHealthController extends Controller
{
    public function index(SystemHealth $health): View
    {
        $report = $health->report();
        $report['queue']['failed'] = DB::table('failed_jobs')->latest('failed_at')->limit(50)->get()
            ->map(fn ($job) => [
                'uuid' => $job->uuid,
                'failed_at' => Carbon::parse($job->failed_at),
                'name' => json_decode($job->payload, true)['displayName'] ?? '?',
                'error' => str($job->exception)->before("\n")->limit(200)->toString(),
            ]);

        return view('admin.health.index', $report);
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
