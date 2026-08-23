<?php

namespace App\Http\Middleware;

use App\Support\ActiveBusiness;
use App\Support\ActiveTenant;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role === 'super_admin' && ! $request->routeIs('platform.*')) {
            return redirect()->route('platform.dashboard');
        }

        if ($request->user()?->role === 'client_portal') {
            return redirect()->route('portal.dashboard');
        }

        abort_unless(ActiveTenant::id(), 403, 'No organisation is assigned to this login.');
        abort_unless(ActiveBusiness::id(), 403, 'No business workspace is assigned to this login.');

        return $next($request);
    }
}
