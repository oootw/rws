<?php

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\ResolvePublicPlace;
use App\Http\Middleware\ResolveTenantBySubdomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Interface/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => ResolveTenantBySubdomain::class,
            'subscription.active' => EnsureSubscriptionActive::class,
            'resolve.public.place' => ResolvePublicPlace::class,
        ]);

        // Доверяем X-Forwarded-* от reverse-proxy (Caddy/Traefik) внутри Docker-сети.
        // Без этого url()/signed routes/Tinkoff URL соберутся с http:// — Telegram
        // webhook и Tinkoff требуют https.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
