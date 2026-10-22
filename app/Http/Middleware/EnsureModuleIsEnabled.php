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
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($this->modules->has($module), 404);
        abort_unless($this->modules->enabled($module), 404);

        return $next($request);
    }
}
