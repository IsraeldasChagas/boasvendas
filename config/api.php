<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versão pública da API
    |--------------------------------------------------------------------------
    */
    'version' => '1.0',

    /*
    |--------------------------------------------------------------------------
    | Nome do sistema nas respostas JSON
    |--------------------------------------------------------------------------
    */
    'system_name' => 'VendaFácil',

    /*
    |--------------------------------------------------------------------------
    | Prefixo dos tokens em texto puro (apenas na criação; nunca no banco)
    |--------------------------------------------------------------------------
    */
    'token_prefix' => 'vf_',

    /*
    |--------------------------------------------------------------------------
    | Ambientes permitidos por token
    |--------------------------------------------------------------------------
    */
    'environments' => [
        'homologacao' => 'Homologação',
        'producao' => 'Produção',
    ],

    /*
    |--------------------------------------------------------------------------
    | Abilities disponíveis (genéricas — não amarradas a um cliente)
    |--------------------------------------------------------------------------
    */
    'abilities' => [
        'api.visualizar' => 'Visualizar status e metadados da API',
        'api.configurar' => 'Configurar integrações',
        'api.tokens.criar' => 'Criar tokens (uso interno/painel)',
        'api.tokens.revogar' => 'Revogar tokens (uso interno/painel)',
        'api.logs.visualizar' => 'Visualizar logs da API',
        'api.webhooks.configurar' => 'Configurar webhooks',
        // Reservadas para a Fase 2+ (ainda sem endpoints)
        'pedidos.api.criar' => 'Criar pedidos via API',
        'pedidos.api.consultar' => 'Consultar pedidos via API',
        'pedidos.api.cancelar' => 'Cancelar pedidos via API',
        'vendas.api.consultar' => 'Consultar vendas via API',
        'caixa.api.consultar' => 'Consultar caixa via API',
        'fiscal.api.emitir' => 'Emitir documentos fiscais via API',
        'fiscal.api.cancelar' => 'Cancelar documentos fiscais via API',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipos de integração futuros (rótulos apenas — sem código específico)
    |--------------------------------------------------------------------------
    */
    'integration_types' => [
        'sas_estoque' => 'SAS-Estoque',
        'mobile' => 'Aplicativo Mobile',
        'loja_virtual' => 'Loja Virtual',
        'marketplace' => 'Marketplace',
        'erp' => 'ERP',
        'outro' => 'Outro / genérico',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limit (requisições por minuto por token ou IP)
    |--------------------------------------------------------------------------
    */
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),

    /*
    |--------------------------------------------------------------------------
    | CORS — origens adicionais (separadas por vírgula). Vazio = refletir request
    | quando em desenvolvimento; em produção configure explicitamente.
    |--------------------------------------------------------------------------
    */
    'cors_allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('API_CORS_ALLOWED_ORIGINS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Idempotência (Fase 1: apenas reconhece o header; não persiste replay)
    |--------------------------------------------------------------------------
    */
    'idempotency_header' => 'Idempotency-Key',

];
