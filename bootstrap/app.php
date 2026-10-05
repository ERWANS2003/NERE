<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/intranet.php'));
        },
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('sla:verifier')->everyFiveMinutes();
        $schedule->command('intranet:check-sla')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Force UTF-8 encoding on all responses
        $middleware->append(\App\Http\Middleware\EnsureUtf8Response::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // Behind a reverse proxy that terminates TLS (IIS or nginx on the Windows
        // server), PHP sees a plain HTTP connection and the real scheme only in
        // X-Forwarded-Proto. Trusting the proxy lets $request->isSecure() and the
        // generated URLs reflect the scheme the browser actually used.
        //
        // Unset by default: without it, and with no reverse proxy, the scheme is
        // taken from the request as-is. Set TRUSTED_PROXIES=* in .env only when a
        // proxy is genuinely in front of the app.
        if (filled($trustedProxies = env('TRUSTED_PROXIES'))) {
            $middleware->trustProxies(
                at: $trustedProxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_AWS_ELB,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        // Show full error details for debugging
        $exceptions->report(function (\Throwable $e) {
            \Log::error('EXCEPTION: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
            ]);
        });
    })->create();
