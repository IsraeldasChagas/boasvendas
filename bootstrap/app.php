<?php

use App\Http\Middleware\EnsureEmpresaMenuAccess;
use App\Http\Middleware\EnsureEmpresaPainelAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
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
        // Evita 419 após login em HTTPS atrás de proxy (cPanel, Cloudflare, etc.)
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'empresa.painel' => EnsureEmpresaPainelAccess::class,
            'empresa.menu' => EnsureEmpresaMenuAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
