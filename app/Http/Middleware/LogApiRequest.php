<?php

namespace App\Http\Middleware;

use App\Models\EmpresaApiLog;
use App\Support\Api\ApiTenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Registra uso da API (sem gravar o token completo).
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->has(ApiTenant::ATTR_STARTED_AT)) {
            $request->attributes->set(ApiTenant::ATTR_STARTED_AT, microtime(true));
        }

        /** @var Response $response */
        $response = $next($request);

        $this->writeLog($request, $response, null);

        return $response;
    }

    public function writeLog(Request $request, ?Response $response, ?Throwable $exception): void
    {
        try {
            if (! Schema::hasTable('empresa_api_logs')) {
                return;
            }

            $started = (float) $request->attributes->get(ApiTenant::ATTR_STARTED_AT, microtime(true));
            $durationMs = (int) max(0, round((microtime(true) - $started) * 1000));

            $empresa = ApiTenant::empresa($request);
            $token = ApiTenant::token($request);

            $status = $response?->getStatusCode();
            $error = null;
            if ($exception !== null) {
                $error = mb_substr($exception->getMessage(), 0, 2000);
                $status ??= 500;
            } elseif ($status !== null && $status >= 400) {
                $error = 'HTTP '.$status;
            }

            $endpoint = '/'.ltrim($request->path(), '/');
            // Nunca registrar Authorization / tokens
            EmpresaApiLog::query()->create([
                'empresa_id' => $empresa?->id,
                'empresa_api_token_id' => $token?->id,
                'user_id' => null,
                'method' => strtoupper($request->method()),
                'endpoint' => mb_substr($endpoint, 0, 500),
                'ip' => $request->ip(),
                'status_http' => $status,
                'duration_ms' => $durationMs,
                'error' => $error,
                'idempotency_key' => $request->attributes->get(ApiTenant::ATTR_IDEMPOTENCY),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Logging nunca deve derrubar a API
        }
    }
}
