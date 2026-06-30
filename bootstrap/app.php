<?php

use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetCacheHeaders;
use Illuminate\Auth\AuthenticationException;
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
        // Cache-Control headers untuk HTML/JSON responses.
        // Dipasang global agar konsisten di semua route.
        $middleware->append(SetCacheHeaders::class);

        // Security headers (H-01): X-Frame-Options, CSP, HSTS, nosniff, dll.
        // Dipasang global sebelum SetCacheHeaders agar response yang sudah
        // ada cache headers masih punya security headers.
        $middleware->append(SecurityHeaders::class);

        // Alias middleware kontrol akses berbasis role (R2). Dipakai pada
        // route per modul, mis. 'module:partner' (lihat routes/web.php).
        $middleware->alias([
            'module' => EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle expired session for Livewire/AJAX requests properly.
        // Without this, Livewire gets a 302 redirect HTML response it can't
        // parse, showing a generic error instead of redirecting to login.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('livewire/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('login'));
        });
    })->create();
