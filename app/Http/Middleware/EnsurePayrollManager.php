<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePayrollManager
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->hasPermission('payroll.manage'),
            403,
            'La paie est réservée aux gestionnaires de paie.'
        );

        return $next($request);
    }
}
