<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte desactive (depart, fin de contrat, radiation) doit perdre ses acces
 * immediatement, y compris si sa session etait deja ouverte. La desactivation
 * n'est donc pas un simple filtre a la connexion : elle est reevaluee a chaque
 * requete authentifiee.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['identifiant' => trans('auth.inactive')]);
        }

        return $next($request);
    }
}
