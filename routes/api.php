<?php

use App\Http\Controllers\Api\V1\IntegrationStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Vendaffacil — versionada (/api/v1/...)
|--------------------------------------------------------------------------
| Autenticação: Bearer Token (empresa_api_tokens). Sem sessão web.
| Fase 1: apenas infraestrutura + GET /integration/status
*/

Route::prefix('v1')
    ->middleware([
        'api.idempotency',
        'api.tenant',
        'throttle:api',
        'api.log',
    ])
    ->group(function () {
        Route::get('/integration/status', IntegrationStatusController::class)
            ->middleware('api.ability:api.visualizar')
            ->name('api.v1.integration.status');

        /*
        | Fase 2+ (não implementar agora):
        | - produtos, clientes, pedidos, delivery, vendas, PDV, caixa, fiscal
        */
    });
