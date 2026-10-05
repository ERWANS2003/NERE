<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force un charset UTF-8 explicite sur toutes les reponses.
 *
 * L'intranet est entierement en francais. IIS sert sinon du latin-1 sur
 * certaines configurations, ce qui transforme les caracteres accentues en
 * Sequence de remplacement sur le poste de l'utilisateur. Le Content-Type est
 * donc complete plutot que remplace : un `text/plain` ou un `application/json`
 * garde son type d'origine.
 */
class EnsureUtf8Response
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'text/html; charset=utf-8');

            return $response;
        }

        if (str_contains($response->headers->get('Content-Type'), 'charset') === false) {
            $response->headers->set(
                'Content-Type',
                $response->headers->get('Content-Type').'; charset=utf-8',
            );
        }

        return $response;
    }
}
