<?php

use App\Http\Middleware\JwtClientMiddleware;
use App\Http\Middleware\JwtStaffMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantScopeMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleCors::class);
        $middleware->alias([
            'auth.staff'   => JwtStaffMiddleware::class,
            'auth.client'  => JwtClientMiddleware::class,
            'tenant.scope' => TenantScopeMiddleware::class,
            'role'         => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
