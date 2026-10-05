<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Form;
use App\Models\Submission;
use App\Policies\DepartmentPolicy;
use App\Policies\FormPolicy;
use App\Policies\SubmissionPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Les policies sont declarees explicitement : une autorisation ne doit
        // jamais reposer sur le seul nom de methode devine par convention.
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Form::class, FormPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);

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