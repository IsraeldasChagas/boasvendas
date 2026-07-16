<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use App\Support\Api\ApiTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationStatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $empresa = ApiTenant::requireEmpresa($request);
        $token = ApiTenant::requireToken($request);

        return ApiResponse::success([
            'success' => true,
            'system' => (string) config('api.system_name', 'VendaFácil'),
            'api_version' => (string) config('api.version', '1.0'),
            'laravel' => app()->version(),
            'company' => [
                'id' => (string) $empresa->id,
                'name' => (string) $empresa->nome,
            ],
            'environment' => (string) $token->environment,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
