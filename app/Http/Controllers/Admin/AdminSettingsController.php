<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', [
            'settings' => [
                'site_name' => AppSetting::value('site_name', config('app.name')),
                'support_email' => AppSetting::value('support_email'),
                'ldap_sync_frequency' => AppSetting::value('ldap_sync_frequency', 'manual'),
                'ldap_sync_time' => AppSetting::value('ldap_sync_time', '02:00'),
                'ldap_sync_day' => AppSetting::value('ldap_sync_day', '1'),
            ],
            'ldapEnabled' => (bool) config('ldap_integration.enabled'),
            'lastSyncAt' => AppSetting::value('ldap_last_sync_at'),
            'lastSyncSummary' => json_decode(AppSetting::value('ldap_last_sync_summary', '[]'), true) ?: [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'ldap_sync_frequency' => ['required', Rule::in(['manual', 'hourly', 'daily', 'weekly'])],
            'ldap_sync_time' => ['required', 'date_format:H:i'],
            'ldap_sync_day' => ['required', 'integer', 'between:1,7'],
        ]);

        AppSetting::saveValues([
            'site_name' => $validated['site_name'],
            'support_email' => $validated['support_email'] ?: null,
            'ldap_sync_frequency' => $validated['ldap_sync_frequency'],
            'ldap_sync_time' => $validated['ldap_sync_time'],
            'ldap_sync_day' => (string) $validated['ldap_sync_day'],
        ]);

        return redirect()->route('admin.settings')->with('success', 'Paramètres enregistrés.');
    }

    public function synchronizeLdap(): RedirectResponse
    {
        if (! config('ldap_integration.enabled')) {
            return back()->with('error', 'La connexion Active Directory est désactivée dans la configuration du serveur.');
        }

        try {
            $process = new Process([PHP_BINARY, base_path('artisan'), 'ldap:sync', '--no-interaction'], base_path());
            $process->setTimeout(600);
            $process->run();
            $output = trim($process->getOutput().$process->getErrorOutput());

            if (! $process->isSuccessful()) {
                Log::error('Synchronisation AD manuelle échouée.', ['sortie' => $output]);

                return back()->with('error', 'La synchronisation AD a échoué. Consultez les journaux de l’application.');
            }
        } catch (\Throwable $exception) {
            Log::error('Synchronisation AD manuelle impossible.', ['exception' => $exception]);

            return back()->with('error', 'La synchronisation AD a échoué. Consultez les journaux de l’application.');
        }

        return back()->with('success', $output !== ''
            ? 'Synchronisation AD terminée. '.$output
            : 'Synchronisation AD terminée.');
    }
}
