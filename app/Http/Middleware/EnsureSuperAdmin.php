<?php

namespace App\Http\Middleware;

use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user()?->role === 'super_admin', 403, 'Platform owner access is required.');

        ActiveTenant::clear();
        ActiveBusiness::clear();

        return $next($request);
    }
}
