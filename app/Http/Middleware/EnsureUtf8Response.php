<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUtf8Response
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force toutes les réponses à utiliser UTF-8
        $response = $next($request);

        // Ajouter le charset UTF-8 au Content-Type
        if (!$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        } elseif (strpos($response->headers->get('Content-Type'), 'charset') === false) {
            $contentType = $response->headers->get('Content-Type');
            $response->headers->set('Content-Type', $contentType . '; charset=utf-8');
        }

        return $response;
    }
}
