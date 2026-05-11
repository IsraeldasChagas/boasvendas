<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Chave usada no backend (Geocoding / Distance Matrix). Não coloque no JavaScript
    | sem restrições HTTP no Google Cloud (referrer / IP).
    */
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'default_origin_address' => env('GOOGLE_MAPS_DEFAULT_ORIGIN_ADDRESS'),
    ],

    /*
    | Frete por km com OSRM + Nominatim (OpenStreetMap). Sem chave de API;
    | em produção use seu próprio Nominatim/OSRM ou URLs públicas com moderação.
    */
    'osm_routing' => [
        // Se existir no .env mas estiver vazio (''), o Laravel não usa o default — normaliza aqui.
        'osrm_base_url' => ($u = trim((string) env('OSRM_BASE_URL', ''))) !== ''
            ? $u
            : 'https://router.project-osrm.org',
        'nominatim_base_url' => ($n = trim((string) env('NOMINATIM_BASE_URL', ''))) !== ''
            ? $n
            : 'https://nominatim.openstreetmap.org',
        'http_user_agent' => ($a = trim((string) env('OSM_HTTP_USER_AGENT', ''))) !== ''
            ? $a
            : 'VendAffacil/1.0 (frete por distância; contacte o suporte do sistema)',
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP fidelidade (código no WhatsApp do cliente)
    |--------------------------------------------------------------------------
    | POST JSON para URL configurada: { "phone": "5511999999999", "message": "..." }
    | Chaves customizáveis (ex.: provedores que usam "to" / "body").
    */
    'fidelidade_otp' => [
        'notify_url' => trim((string) env('FIDELIDADE_OTP_NOTIFY_URL', '')),
        'notify_bearer' => trim((string) env('FIDELIDADE_OTP_NOTIFY_BEARER', '')),
        /** bearer (Authorization: Bearer), apikey (header customizado, ex. Evolution), none */
        'notify_auth_type' => strtolower(trim((string) env('FIDELIDADE_OTP_NOTIFY_AUTH_TYPE', 'bearer'))) ?: 'bearer',
        'notify_apikey_header' => trim((string) env('FIDELIDADE_OTP_NOTIFY_APIKEY_HEADER', 'apikey')) ?: 'apikey',
        'json_phone_key' => trim((string) env('FIDELIDADE_OTP_JSON_PHONE_KEY', 'phone')) ?: 'phone',
        'json_message_key' => trim((string) env('FIDELIDADE_OTP_JSON_MESSAGE_KEY', 'message')) ?: 'message',
        /** Em produção sem URL: se true, grava OTP e segue o fluxo (apenas log; não envia WhatsApp real). */
        'simulate_without_url' => filter_var(env('FIDELIDADE_OTP_SIMULATE_WITHOUT_URL', false), FILTER_VALIDATE_BOOL),
    ],

];
