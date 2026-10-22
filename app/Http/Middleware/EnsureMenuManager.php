<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuManager
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->hasPermission('menu.manage'),
            403,
            'La gestion des menus est réservée aux utilisateurs autorisés.'
        );

        return $next($request);
    }
}
