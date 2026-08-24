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
        $middleware->redirectGuestsTo('/auth/signin');

        // Exclude API routes from CSRF validation
        $middleware->validateCsrfTokens(except: [
            'ipn/*',
            'comment/campaign-activities/generate',
            'api/*',
            'unipile/callback',
        ]);
        
        // Add CORS middleware to API routes
        $middleware->appendToGroup('api', \App\Http\Middleware\CorsMiddleware::class);
        
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'v2.extension.token' => \App\Http\Middleware\EnsureV2ExtensionToken::class,
            'v2.idempotency' => \App\Http\Middleware\EnsureIdempotencyKey::class,
            'v2.tenant' => \App\Http\Middleware\EnsureV2TenantContext::class,
            'v2.capability' => \App\Http\Middleware\EnsureV2Capability::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 
    })->create();
