<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

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
            'lastSyncAttemptAt' => AppSetting::value('ldap_last_sync_attempt_at'),
            'lastSyncError' => AppSetting::value('ldap_last_sync_error'),
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

        $process = null;

        try {
            $php = (new PhpExecutableFinder)->find();
            if ($php === false) {
                throw new \RuntimeException('Aucun exécutable PHP CLI n’a été trouvé sur le serveur.');
            }

            $process = new Process([$php, base_path('artisan'), 'ldap:sync', '--no-interaction'], base_path());
            $process->setTimeout(600);
            $process->run();
            $output = trim($process->getOutput().$process->getErrorOutput());

            if (! $process->isSuccessful()) {
                $diagnostic = $this->syncDiagnostic($output, 'La commande ldap:sync a échoué sans fournir de diagnostic.');
                AppSetting::saveValues([
                    'ldap_last_sync_attempt_at' => now()->toIso8601String(),
                    'ldap_last_sync_error' => $diagnostic,
                ]);
                Log::error('Synchronisation AD manuelle échouée.', ['sortie' => $diagnostic]);

                return back()->with('error', 'La synchronisation AD a échoué : '.$diagnostic);
            }
        } catch (Throwable $exception) {
            $output = $process
                ? trim($process->getOutput().$process->getErrorOutput()."\n".$exception->getMessage())
                : $exception->getMessage();
            $diagnostic = $this->syncDiagnostic($output, 'Impossible de démarrer la commande ldap:sync.');
            AppSetting::saveValues([
                'ldap_last_sync_attempt_at' => now()->toIso8601String(),
                'ldap_last_sync_error' => $diagnostic,
            ]);
            Log::error('Synchronisation AD manuelle impossible.', ['exception' => $exception]);

            return back()->with('error', 'La synchronisation AD a échoué : '.$diagnostic);
        }

        AppSetting::saveValues([
            'ldap_last_sync_attempt_at' => now()->toIso8601String(),
            'ldap_last_sync_error' => null,
        ]);

        return back()->with('success', $output !== ''
            ? 'Synchronisation AD terminée. '.$output
            : 'Synchronisation AD terminée.');
    }

    private function syncDiagnostic(string $output, string $fallback): string
    {
        $output = preg_replace('/(LDAP_PASSWORD\s*[=:]\s*)\S+/i', '$1[masqué]', trim($output)) ?? '';

        return mb_substr($output !== '' ? $output : $fallback, 0, 1500);
    }
}
