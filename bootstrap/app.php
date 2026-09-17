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
    ->withMiddleware(function (Middleware $middleware) {
        // Trusts X-Forwarded-* from any upstream. Standard starting point for
        // apps behind a reverse proxy (even a same-box Nginx-in-front-of-PHP-FPM
        // setup already counts). Without this, $request->ip() returns the
        // proxy's address for every request — which would make the
        // throttle:login rate limiter (keyed by IP) useless, and can also
        // affect HTTPS detection and generated URLs. Worth narrowing to
        // specific known proxy IPs once real production infrastructure
        // (load balancer / CDN) is finalized — see the scalability phase.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleAppearance::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'patient.auth' => \App\Http\Middleware\PatientAuth::class,
            'doctor.auth'  => \App\Http\Middleware\DoctorAuth::class,
            'admin.auth'   => \App\Http\Middleware\AdminAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
