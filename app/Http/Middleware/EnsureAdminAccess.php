<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role === 'client_portal') {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
