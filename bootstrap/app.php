<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'permission'       => \App\Http\Middleware\AdminPermissionMiddleware::class,
            'verified_relawan' => \App\Http\Middleware\EnsureRelawanVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Memastikan seluruh response endpoint /api/* selalu berformat JSON dalam mode production
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });
    })->create();
