<?php

namespace App\Http\Middleware;

use App\Services\IamService;
use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if ($request->user()) {
            app(IamService::class)->bootstrap();
        }

        abort_unless(
            $request->user() && $request->user()->hasPermission($permission),
            403,
            'You do not have permission to perform this action.'
        );

        return $next($request);
    }
}
