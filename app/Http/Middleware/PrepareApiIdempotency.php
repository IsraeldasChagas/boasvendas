<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase 1: apenas captura Idempotency-Key para logs/contexto.
 * Persistência e replay serão implementados em fase posterior.
 */
class PrepareApiIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) config('api.idempotency_header', 'Idempotency-Key');
        $key = $request->header($header);
        if (is_string($key) && trim($key) !== '') {
            $request->attributes->set(ApiTenant::ATTR_IDEMPOTENCY, mb_substr(trim($key), 0, 128));
        }

        return $next($request);
    }
}
