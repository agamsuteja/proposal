<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\RecordActivity;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['active' => EnsureActiveAccount::class, 'role' => RequireRole::class]);
        $middleware->web(append: [SecurityHeaders::class, RecordActivity::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
