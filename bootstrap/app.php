<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureUserRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'role' => EnsureUserRole::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login'),
        );
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->user()?->isAdmin()
                ? route('admin.dashboard')
                : route('dashboard'),
        );
        $middleware->trustProxies();
        $middleware->validateCsrfTokens(except: [
            'internal/render/reminders',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
