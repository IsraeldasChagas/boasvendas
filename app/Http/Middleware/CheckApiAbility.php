<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use App\Support\Api\ApiTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida se o token autenticado possui a ability exigida pela rota.
 */
class CheckApiAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $token = ApiTenant::token($request);
        if ($token === null) {
            return ApiResponse::error(
                'Token de autenticação ausente.',
                401,
                'api.unauthenticated'
            );
        }

        if ($abilities === []) {
            return $next($request);
        }

        foreach ($abilities as $ability) {
            if ($token->tokenCan($ability)) {
                return $next($request);
            }
        }

        return ApiResponse::error(
            'Token sem permissão para esta operação.',
            403,
            'api.forbidden',
            ['required_abilities' => array_values($abilities)]
        );
    }
}
