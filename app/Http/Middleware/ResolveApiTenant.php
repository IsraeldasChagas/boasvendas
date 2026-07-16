<?php

namespace App\Http\Middleware;

use App\Models\EmpresaApiToken;
use App\Support\Api\ApiResponse;
use App\Support\Api\ApiTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida Bearer token, resolve a empresa (tenant) e impede acesso cruzado.
 * Não utiliza sessão web.
 */
class ResolveApiTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(ApiTenant::ATTR_STARTED_AT, microtime(true));

        $plain = $this->extractBearerToken($request);
        if ($plain === null) {
            return ApiResponse::error(
                'Token de autenticação ausente. Envie Authorization: Bearer {token}.',
                401,
                'api.unauthenticated'
            );
        }

        $token = EmpresaApiToken::findValidByPlainToken($plain);
        if ($token === null) {
            return ApiResponse::error(
                'Token inválido, expirado ou revogado.',
                401,
                'api.invalid_token'
            );
        }

        if (! $token->allowsIp($request->ip())) {
            return ApiResponse::error(
                'IP não autorizado para este token.',
                403,
                'api.ip_forbidden'
            );
        }

        $empresa = $token->empresa;
        if ($empresa === null) {
            return ApiResponse::error(
                'Empresa vinculada ao token não encontrada.',
                401,
                'api.company_missing'
            );
        }

        if ((int) $token->empresa_id !== (int) $empresa->id) {
            return ApiResponse::error(
                'Inconsistência de tenant no token.',
                403,
                'api.tenant_mismatch'
            );
        }

        ApiTenant::set($request, $empresa, $token);
        $token->touchLastUsed();

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (! is_string($header) || $header === '') {
            return null;
        }

        if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m) !== 1) {
            return null;
        }

        $plain = trim($m[1]);

        return $plain !== '' ? $plain : null;
    }
}
