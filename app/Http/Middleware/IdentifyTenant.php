<?php

namespace App\Http\Middleware;

use App\Support\ActiveTenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        if ($tenant = ActiveTenant::fromRequest()) {
            ActiveTenant::switchTo($tenant);
        } elseif (auth()->check()) {
            ActiveTenant::current();
        }

        return $next($request);
    }
}
