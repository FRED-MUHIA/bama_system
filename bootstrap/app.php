<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\RecordMajorActivity;
use App\Http\Middleware\RequirePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['logout']);
        $middleware->web(append: [IdentifyTenant::class, RecordMajorActivity::class]);
        $middleware->alias([
            'admin' => EnsureAdminAccess::class,
            'permission' => RequirePermission::class,
            'super_admin' => EnsureSuperAdmin::class,
            'tenant.context' => IdentifyTenant::class,
            'module.enabled' => EnsureModuleEnabled::class,
            'subscription.active' => EnsureSubscriptionActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
