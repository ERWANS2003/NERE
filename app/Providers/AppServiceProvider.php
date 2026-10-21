<?php

namespace App\Providers;

use App\Jobs\SendWebPushNotification;
use App\Models\Announcement;
use App\Models\AppSetting;
use App\Models\Department;
use App\Models\Resource;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    private function notificationPath(mixed $url): string
    {
        if (! is_string($url)) {
            return '/';
        }

        $parts = parse_url($url);
        if (! is_array($parts)
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return '/';
        }

        if (isset($parts['host'])) {
            $applicationHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if (! is_string($applicationHost)
                || strtolower($parts['host']) !== strtolower($applicationHost)
                || (isset($parts['scheme']) && ! in_array(strtolower($parts['scheme']), ['http', 'https'], true))) {
                return '/';
            }

            return ($parts['path'] ?? '/')
                .(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        if (isset($parts['scheme']) || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return '/';
        }

        return $url;
    }

    public function boot(): void
    {
        // Une seule politique de mot de passe pour toute l'application : reponse a
        // `Password::defaults()`, elle s'applique donc aux deux endroits ou un
        // utilisateur peut fixer son propre mot de passe (reinitialisation et page
        // "mon compte"). Un mot de passe d administration, lui, passe par une autre
        // voie et sera traite a l'etape des comptes.
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        // Les policies sont declarees explicitement : une autorisation ne doit
        // jamais reposer sur le seul nom de methode devine par convention.
        // FormPolicy et SubmissionPolicy arriveront avec les etapes 3 et 4.
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Resource::class, ResourcePolicy::class);

        Event::listen(NotificationSent::class, function (NotificationSent $event): void {
            if (! config('webpush.enabled')
                || $event->channel !== 'database'
                || ! ($event->notifiable instanceof User)) {
                return;
            }

            $data = is_object($event->response) ? ($event->response->data ?? null) : null;

            if (! is_array($data)) {
                throw new \UnexpectedValueException('Les données de notification enregistrée doivent être un tableau.');
            }

            $payload = [
                'title' => is_string($data['title'] ?? null) ? $data['title'] : config('app.name'),
                'body' => is_string($data['message'] ?? null) ? $data['message'] : '',
                'url' => $this->notificationPath($data['url'] ?? null),
                'tag' => (string) ($event->notification->id ?? ''),
            ];

            foreach ($event->notifiable->pushSubscriptions()->pluck('id') as $subscriptionId) {
                SendWebPushNotification::dispatch((int) $subscriptionId, $payload);
            }
        });

        View::composer('*', static function ($view): void {
            $settings = AppSetting::query()
                ->whereIn('key', ['site_name', 'support_email'])
                ->pluck('value', 'key');
            $siteName = $settings->get('site_name') ?: config('app.name');
            $supportEmail = $settings->get('support_email');

            config([
                'app.name' => $siteName,
                'app.support_email' => $supportEmail,
            ]);

            $view->with('siteName', $siteName);
            $view->with('supportEmail', $supportEmail);
        });

        // Environnement de developpement : les acces massifs aux relations sont
        // un gain de temps, ils sont interdits en production pour ne pas
        // transformer un oubli de `with()` en requete N+1 silencieuse.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::unguard(false);

        // Permissions de dossiers sur les serveur Windows / IIS.
        if (file_exists(storage_path())) {
            @chmod(storage_path(), 0775);
            @chmod(storage_path('logs'), 0775);
            @chmod(storage_path('app'), 0775);
            @chmod(storage_path('framework'), 0775);
        }
        if (file_exists(base_path('bootstrap/cache'))) {
            @chmod(base_path('bootstrap/cache'), 0775);
        }

        // Le schema https n'est impose que si le deploiement termine vraiment le
        // TLS. Decider sur APP_ENV seul casse le serveur integre de PHP, qui n'a
        // pas d'ecouteur TLS : le navigateur recupere alors
        // "Invalid request (Unsupported SSL request)".
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
