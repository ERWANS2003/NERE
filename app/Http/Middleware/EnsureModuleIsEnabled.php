<?php

namespace App\Http\Middleware;

use App\Support\IntranetModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleIsEnabled
{
    public function __construct(private readonly IntranetModuleRegistry $modules) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, ?string $preview = null): Response
    {
        abort_unless($this->modules->has($module), 404);

        // « module:x,admins » : un module désactivé reste accessible aux
        // administrateurs système, pour le vérifier avant de l'ouvrir à tous.
        $adminPreview = $preview === 'admins' && $request->user()?->is_super_admin;
        abort_unless($this->modules->enabled($module) || $adminPreview, 404);

        return $next($request);
    }
}
