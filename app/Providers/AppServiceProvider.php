<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer le Plugin Manager comme singleton
        $this->app->singleton(\App\Core\PluginSystem\PluginManager::class, function ($app) {
            return new \App\Core\PluginSystem\PluginManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every ability referenced by a `can:` middleware or an @can directive is
        // defined here, so route guards and view guards can never disagree.
        $roleGate = function ($user, array $roles, ?string $permission = null): bool {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return $permission !== null && $user->hasPermission($permission);
        };

        Gate::define('approve-service-requests', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi', 'directeur_departement'],
            'services.approve'
        ));

        Gate::define('manage-automations', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi'],
            'automations.manage'
        ));

        Gate::define('view_all_tickets', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi', 'directeur_departement', 'technicien']
        ));

        Gate::define('manage-sla', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi'],
            'sla.manage'
        ));

        Gate::define('view_reports', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi', 'directeur_departement'],
            'reports.export'
        ));

        Gate::define('manage_assets', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi', 'technicien']
        ));

        Gate::define('manage_department', fn ($user): bool => $roleGate(
            $user,
            ['admin', 'dsi', 'directeur_departement'],
            'department.manage_team'
        ));

        // The safety routes are guarded by the `permission:` middleware, which
        // resolves through the permissions table. These gates mirror that so a
        // view guard and a route guard resolve the same question.
        Gate::define('safety.view', fn ($user): bool => $user->hasPermission('safety.view'));
        Gate::define('safety.manage', fn ($user): bool => $user->hasPermission('safety.manage'));

        // Ensure storage directories have proper permissions
        if (file_exists(storage_path())) {
            @chmod(storage_path(), 0775);
            @chmod(storage_path('logs'), 0775);
            @chmod(storage_path('app'), 0775);
        }
        if (file_exists(base_path('bootstrap/cache'))) {
            @chmod(base_path('bootstrap/cache'), 0775);
        }

        // Force the https scheme on generated URLs, but only when the deployment
        // actually terminates TLS. Deciding this from APP_ENV alone is wrong: the
        // PHP built-in server has no TLS listener, so forcing https locally makes
        // the browser handshake a plain HTTP port and get
        // "Invalid request (Unsupported SSL request)" back.
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        // Register custom Blade directives
        \Illuminate\Support\Facades\Blade::if('hasRole', function (string $role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        // NOTE: `@can` is intentionally NOT overridden here. A `Blade::if('can')`
        // registration shadows Laravel's built-in @can directive and silently
        // swapped Gate semantics for a permissions-table lookup, which is how
        // the sidebar lost its Kanban / SLA entries: the view asked for
        // `view_all_tickets` while the route group asked the Gate.

        // DISABLED: Charger le système de plugins - causes 500 errors
        // $pluginManager = app(\App\Core\PluginSystem\PluginManager::class);
        // $pluginManager->loadAll();
    }
}
