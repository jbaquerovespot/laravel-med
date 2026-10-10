<?php

use App\Http\Middleware\FhirAuditMiddleware;
use App\Http\Middleware\FhirAuthMiddleware;
use App\Http\Middleware\FhirContentNegotiationMiddleware;
use App\Http\Middleware\FhirRateLimiter;
use App\Http\Middleware\SessionTimeout;
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
        // HCE Authentication middleware aliases
        $middleware->alias([
            'session.timeout' => SessionTimeout::class,
            'fhir.auth' => FhirAuthMiddleware::class,
            'fhir.audit' => FhirAuditMiddleware::class,
            'fhir.content' => FhirContentNegotiationMiddleware::class,
            'fhir.rate' => FhirRateLimiter::class,
        ]);

        // FHIR API middleware group
        $middleware->group('fhir', [
            FhirAuthMiddleware::class,
            FhirAuditMiddleware::class,
            FhirContentNegotiationMiddleware::class,
            FhirRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
