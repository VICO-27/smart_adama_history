<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | Restricted to FRONTEND_URL only — never wildcard in production.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('FRONTEND_URL_ALT', ''),          // optional second origin
        'http://localhost:5173',
        'http://localhost:5174',
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],

    'max_age' => 0,

    'supports_credentials' => false,

];
