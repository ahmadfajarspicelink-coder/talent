<?php

use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\SetCacheHeaders;
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
        // Cache-Control headers untuk HTML/JSON responses.
        // Dipasang global agar konsisten di semua route.
        $middleware->append(SetCacheHeaders::class);

        // Alias middleware kontrol akses berbasis role (R2). Dipakai pada
        // route per modul, mis. 'module:partner' (lihat routes/web.php).
        $middleware->alias([
            'module' => EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
