<?php

use App\Http\Middleware\EnsureEmpresaMenuAccess;
use App\Http\Middleware\EnsureEmpresaPainelAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\PreventStaleFormCache;
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
        // Evita 419 após login em HTTPS atrás de proxy (cPanel, Cloudflare, etc.)
        $middleware->trustProxies(at: '*');

        $middleware->appendToGroup('web', PreventStaleFormCache::class);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'empresa.painel' => EnsureEmpresaPainelAccess::class,
            'empresa.menu' => EnsureEmpresaMenuAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sessão expirou ou a página estava desatualizada. Atualize e tente de novo.',
                ], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except([
                    '_token',
                    'password',
                    'password_confirmation',
                    'admin_password',
                    'admin_password_confirmation',
                ]))
                ->with(
                    'error',
                    'Sua sessão expirou ou a página ficou antiga (erro 419). Os dados foram mantidos: confira e clique em salvar novamente. Se o aviso continuar, atualize a página (F5) antes de enviar.'
                );
        });
    })->create();
