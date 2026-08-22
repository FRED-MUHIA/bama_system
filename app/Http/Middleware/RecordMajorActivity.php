<?php

namespace App\Http\Middleware;

use App\Support\ActiveBusiness;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class RecordMajorActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $route = $request->route()?->getName();

        if (! auth()->check() || ! $route || $response->getStatusCode() >= 400 || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return $response;
        if (str_starts_with($route, 'administration.') || in_array($route, ['logout', 'profile.update', 'businesses.switch'], true)) return $response;

        $major = ['store', 'update', 'destroy', 'approve', 'archive', 'convert', 'payment', 'reconcile', 'close', 'reverse', 'unreverse', 'deliver', 'submit', 'merge', 'import', 'sync', 'acknowledge'];
        if (! collect($major)->contains(fn ($action) => str_contains($route, $action))) return $response;
        if (! Schema::hasTable('admin_audit_logs')) return $response;

        DB::table('admin_audit_logs')->insert([
            'business_id' => ActiveBusiness::id(),
            'user_id' => auth()->id(),
            'event' => 'activity:'.$route,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $response;
    }
}
