<?php

namespace App\Http\Middleware;

use App\Support\ActiveTenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && in_array($user->role, ['super_admin', 'client_portal'], true)) {
            ActiveTenant::clear();

            return $next($request);
        }

        if ($tenant = ActiveTenant::fromRequest()) {
            if ($user && ! ActiveTenant::userCanAccessTenant($user, (int) $tenant->id)) {
                abort(403, 'This login is not assigned to this organisation.');
            }

            ActiveTenant::switchTo($tenant);
        } elseif (auth()->check()) {
            ActiveTenant::current();
        }

        return $next($request);
    }
}
