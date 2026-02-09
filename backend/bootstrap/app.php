<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'user.blocked' => \App\Http\Middleware\CheckUserBlocked::class,
            'session.limit' => \App\Http\Middleware\SessionLimit::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);
        
        // Check account expiration for all web routes
        $middleware->web(append: [
            \App\Http\Middleware\CheckAccountExpiration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
