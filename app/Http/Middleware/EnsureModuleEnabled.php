<?php

namespace App\Http\Middleware;

use App\Services\ModuleRegistry;
use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module)
    {
        abort_unless(app(ModuleRegistry::class)->enabledSlug($module), 404, 'This module is not enabled for the active tenant.');

        return $next($request);
    }
}
