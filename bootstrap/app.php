<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\RedirectDonorSettingsMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', '*'),
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->validateCsrfTokens(except: [
            'unsubscribe/announcements/*',
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'redirect.donor.settings' => RedirectDonorSettingsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if (! $request->expectsJson()) {
                return redirect()->guest(route('login'))
                    ->with('status', 'Your session or security token has expired. Please sign in again.');
            }
        });
    })->create();
