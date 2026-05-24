<?php

return [
    'token' => env('TELEGRAM_BOT_TOKEN'),

    'safe_mode' => env('APP_ENV', 'local') === 'production',

    'config' => [
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
        'bot_name' => env('TELEGRAM_BOT_USERNAME'),
        'conversation_ttl' => 3600,

        // Опции для Guzzle-клиента Nutgram'а. Используется, когда исходящие
        // запросы идут через свой relay (например, из РФ VDS на api.telegram.org)
        // — relay по этому заголовку отфильтрует чужие запросы.
        'client' => [
            'headers' => array_filter([
                'X-Proxy-Secret' => env('TELEGRAM_PROXY_SECRET'),
            ]),
        ],
    ],

    'routes' => true,

    'mixins' => false,

    'namespace' => app_path('Telegram'),

    'log_channel' => env('TELEGRAM_LOG_CHANNEL', 'stack'),
];
