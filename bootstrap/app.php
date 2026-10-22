<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureModuleIsEnabled;
use App\Http\Middleware\EnsureUtf8Response;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Désactiver TOUS les middlewares temporairement pour test
        // $middleware->append(EnsureUtf8Response::class);
        
        // Register custom middleware aliases
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'module' => EnsureModuleIsEnabled::class,
        ]);
        
        $middleware->redirectGuestsTo('/connexion');
        $middleware->redirectUsersTo('/');

        if (filled($trustedProxies = env('TRUSTED_PROXIES'))) {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->expectsJson(),
        );
    })->create();
