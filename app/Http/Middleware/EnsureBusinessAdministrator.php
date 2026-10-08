<?php

namespace App\Http\Middleware;

use App\Services\IamService;
use Closure;
use Illuminate\Http\Request;

class EnsureBusinessAdministrator
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(
            $request->user() && app(IamService::class)->isBusinessAdministrator($request->user()),
            403,
            'Only business administrators can manage company settings.'
        );

        return $next($request);
    }
}
