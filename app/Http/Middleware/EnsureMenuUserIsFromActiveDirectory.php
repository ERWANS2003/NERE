<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuUserIsFromActiveDirectory
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null
                && filled($user->guid)
                && filled($user->domain)
                && $request->session()->get('auth_provider') === 'active_directory',
            403,
            'Le module Menu est réservé aux comptes authentifiés par Active Directory.'
        );

        return $next($request);
    }
}
