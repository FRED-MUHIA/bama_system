<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionManager;
use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role === 'super_admin') {
            return $next($request);
        }

        abort_unless(app(SubscriptionManager::class)->active(), 402, 'The active tenant subscription is not active.');

        return $next($request);
    }
}
