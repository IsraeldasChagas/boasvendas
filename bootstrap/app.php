<?php

use App\Http\Middleware\CheckApiAbility;
use App\Http\Middleware\EnsureEmpresaColaboradorPapel;
use App\Http\Middleware\EnsureEmpresaMenuAccess;
use App\Http\Middleware\EnsureEmpresaPainelAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\PrepareApiIdempotency;
use App\Http\Middleware\PreventStaleFormCache;
use App\Http\Middleware\ResolveApiTenant;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Evita 419 após login em HTTPS atrás de proxy (cPanel, Cloudflare, etc.)
        $middleware->trustProxies(at: '*');

        $middleware->appendToGroup('web', PreventStaleFormCache::class);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'empresa.painel' => EnsureEmpresaPainelAccess::class,
            'empresa.colaborador' => EnsureEmpresaColaboradorPapel::class,
            'empresa.menu' => EnsureEmpresaMenuAccess::class,
            'api.tenant' => ResolveApiTenant::class,
            'api.ability' => CheckApiAbility::class,
            'api.log' => LogApiRequest::class,
            'api.idempotency' => PrepareApiIdempotency::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(
                    'Sessão/CSRF inválido. A API usa Bearer Token, não sessão.',
                    419,
                    'api.csrf'
                );
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

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Dados inválidos.',
                422,
                'api.validation',
                $e->errors()
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                'Não autenticado.',
                401,
                'api.unauthenticated'
            );
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() !== '' ? $e->getMessage() : 'Erro HTTP.';

                return ApiResponse::error($message, $status, 'api.http_'.$status);
            }

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Erro interno ao processar a requisição.';

            return ApiResponse::error($message, 500, 'api.server_error');
        });
    })->create();
