<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // Utilisation dans routes/web.php : ->middleware('role:Admin')
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->slug, $roles, true)) {
            abort(403, 'Accès refusé : rôle insuffisant.');
        }

        return $next($request);
    }
}
