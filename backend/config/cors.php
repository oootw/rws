<?php

/*
|--------------------------------------------------------------------------
| CORS
|--------------------------------------------------------------------------
|
| Owner-панель — SPA на `{slug}.otziv.space/owner`. Используется Sanctum SPA
| cookie-auth, поэтому в dev-режиме (Vite на :5174) нужны разрешённые origin'ы
| и `supports_credentials=true`. В prod SPA отдаётся с того же домена, что и API,
| и CORS фактически не нужен — но конфигурация безопасна.
|
*/

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    // CSV доменов через env: например "http://localhost:5174,https://*.otziv.space".
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:5174')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
