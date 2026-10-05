<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Toute reponse declare explicitement son charset : l'intranet est
        // integralement en francais et IIS peut sinon servir du latin-1, ce qui
        // produit des pages illisibles sur le serveur de production.
        $middleware->append(\App\Http\Middleware\EnsureUtf8Response::class);

        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'dept.role' => \App\Http\Middleware\RequireDepartmentRole::class,
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