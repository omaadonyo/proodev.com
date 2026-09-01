<?php

use App\Http\Middleware\EnsureIpIsNotBlocked;
use App\Http\Middleware\EnsureRecruiterAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsNotSuspended;
use App\Http\Middleware\SanitizeUtf8JsonResponse;
use App\Http\Middleware\TrackLastActivity;
use App\Providers\AuthServiceProvider;
use App\Providers\BroadcastServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withProviders([
        BroadcastServiceProvider::class,
        AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'recruiter.access' => EnsureRecruiterAccess::class,
        ]);

        $middleware->web(append: [
            TrackLastActivity::class,
            EnsureUserIsNotSuspended::class,
            EnsureIpIsNotBlocked::class,
            SanitizeUtf8JsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
