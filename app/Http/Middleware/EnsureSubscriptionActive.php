<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->role === 'super_admin') {
            return $next($request);
        }

        $manager = app(SubscriptionManager::class);
        if (! $manager->active()) {
            if (Route::has('billing.index')) {
                return redirect()->route('billing.index')->with('warning', $manager->billingState()['message']);
            }

            abort(402, 'The active tenant subscription is not active.');
        }

        return $next($request);
    }
}
