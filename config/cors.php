<?php

$originsRaw = env('API_CORS_ALLOWED_ORIGINS', '');
$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) $originsRaw)
)));

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    /*
    | Em produção defina API_CORS_ALLOWED_ORIGINS no .env (URLs separadas por vírgula).
    | Sem origens: '*' (homologação / clientes server-to-server).
    */
    'allowed_origins' => $origins !== [] ? $origins : ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-Request-Id',
        'X-Api-Version',
    ],

    'max_age' => 0,

    'supports_credentials' => false,

];
