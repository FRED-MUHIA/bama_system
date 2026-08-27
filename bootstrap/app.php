<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RecordMajorActivity;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['logout', 'billing/mpesa/callback']);
        $middleware->web(append: [IdentifyTenant::class, RecordMajorActivity::class, SecurityHeaders::class]);
        $middleware->alias([
            'admin' => EnsureAdminAccess::class,
            'permission' => RequirePermission::class,
            'super_admin' => EnsureSuperAdmin::class,
            'tenant.context' => IdentifyTenant::class,
            'module.enabled' => EnsureModuleEnabled::class,
            'subscription.active' => EnsureSubscriptionActive::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please refresh and try again.'], 419);
            }

            $publicPrefix = $request->is('public/*') || $request->routeIs('public.*');

            $loginRoute = match (true) {
                $request->is('owner/login*', 'public/owner/login*') || $request->routeIs('platform.login*', 'public.platform.login*') => $publicPrefix ? 'public.platform.login' : 'platform.login',
                $request->is('portal/login*', 'public/portal/login*') || $request->routeIs('portal.login*', 'public.portal.login*') => $publicPrefix ? 'public.portal.login' : 'portal.login',
                $request->is('login*', 'public/login*') || $request->routeIs('login*', 'public.login*') => $publicPrefix ? 'public.login' : 'login',
                default => null,
            };

            if ($loginRoute) {
                return redirect()->route($loginRoute)->with('warning', 'Your secure login session expired. Please sign in again.');
            }

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation']))
                ->with('warning', 'Your session expired. Please try again.');
        });
    })->create();
