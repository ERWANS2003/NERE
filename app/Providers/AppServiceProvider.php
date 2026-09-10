<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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
        // Force HTTPS on production (Railway)
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Register custom Blade directives
        \Illuminate\Support\Facades\Blade::if('hasRole', function (string $role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        \Illuminate\Support\Facades\Blade::if('can', function (string $permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        // DISABLED: Charger le système de plugins - causes 500 errors
        // $pluginManager = app(\App\Core\PluginSystem\PluginManager::class);
        // $pluginManager->loadAll();
    }
}
