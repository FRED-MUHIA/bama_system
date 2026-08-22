<?php

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
        $middleware->web(append: [\App\Http\Middleware\IdentifyTenant::class, \App\Http\Middleware\RecordMajorActivity::class]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdminAccess::class,
            'permission' => \App\Http\Middleware\RequirePermission::class,
            'tenant.context' => \App\Http\Middleware\IdentifyTenant::class,
            'module.enabled' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
