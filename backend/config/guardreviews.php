<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application domain (multi-tenant subdomains)
    |--------------------------------------------------------------------------
    |
    | Scan URLs: https://{subdomain_slug}.{domain}/s/{place_uuid}
    |
    */

    'domain' => env('APP_DOMAIN', 'otziv.space'),

    /*
    |--------------------------------------------------------------------------
    | TLS on-demand whitelist (Caddy reverse-proxy)
    |--------------------------------------------------------------------------
    | CSV apex-доменов, для которых /api/internal/tls-allow возвращает 200.
    | По умолчанию — APP_DOMAIN. На VDS с одним прокси, обслуживающим оба
    | стенда, укажите: TLS_ALLOWED_DOMAINS="otziv.space,staging.otziv.space"
    */
    'tls_allowed_domains' => env('TLS_ALLOWED_DOMAINS'),

    'admin_alert_email' => env('ADMIN_ALERT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Subscription pricing (amounts in kopecks)
    |--------------------------------------------------------------------------
    */

    'subscription' => [
        'base_price' => (int) env('SUBSCRIPTION_BASE_PRICE', 99000),
        'extra_place_price' => (int) env('SUBSCRIPTION_EXTRA_PLACE_PRICE', 29000),
        'duration_days' => (int) env('SUBSCRIPTION_DURATION_DAYS', 30),
        'reminder_days_before' => (int) env('SUBSCRIPTION_REMINDER_DAYS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data retention
    |--------------------------------------------------------------------------
    */

    'retention_days' => (int) env('DATA_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Telegram bot
    |--------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
        // Список URL'ов через запятую с автоматическим failover'ом.
        // Если задан — TELEGRAM_API_URL игнорируется (первый из списка становится primary).
        // При сбое одного прокси клиент молча переключается на следующий.
        // Пример: TELEGRAM_API_URLS="https://tg1.example.com,https://tg2.example.com"
        'api_urls' => env('TELEGRAM_API_URLS'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MAX Messenger (disabled until Phase 2+)
    |--------------------------------------------------------------------------
    */

    'max' => [
        'enabled' => env('MAX_BOT_ENABLED', false),
        'token' => env('MAX_BOT_TOKEN'),
        'webhook_secret' => env('MAX_BOT_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Founder contacts (QR design orders, support)
    |--------------------------------------------------------------------------
    */

    'founder' => [
        'telegram_username' => env('FOUNDER_TELEGRAM_USERNAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tinkoff Acquiring
    |--------------------------------------------------------------------------
    */

    'tinkoff' => [
        'terminal_key' => env('TINKOFF_TERMINAL_KEY'),
        'secret_key' => env('TINKOFF_SECRET_KEY'),
        'api_url' => env('TINKOFF_API_URL', 'https://securepay.tinkoff.ru/v2'),
        'notification_url' => env('TINKOFF_NOTIFICATION_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/api/webhooks/tinkoff'),
        'success_url' => env('TINKOFF_SUCCESS_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/payment/success'),
        'fail_url' => env('TINKOFF_FAIL_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/payment/fail'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Yandex SmartCaptcha
    |--------------------------------------------------------------------------
    */

    'captcha' => [
        'client_key' => env('YANDEX_CAPTCHA_CLIENT_KEY'),
        'server_key' => env('YANDEX_CAPTCHA_SERVER_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin panel (Filament)
    |--------------------------------------------------------------------------
    |
    | Один супер-админ, хранится в .env:
    |  - ADMIN_EMAIL — логин;
    |  - ADMIN_PASSWORD_HASH — bcrypt-хеш пароля (сгенерируйте через
    |    `php artisan admin:password`).
    |
    | Пустые значения отключают вход в админку.
    */

    /*
    |--------------------------------------------------------------------------
    | Owner login (magic code via Telegram)
    |--------------------------------------------------------------------------
    */

    'owner_login' => [
        'ttl_seconds' => (int) env('OWNER_LOGIN_TTL_SECONDS', 600),
    ],

    'admin' => [
        'path' => env('ADMIN_PANEL_PATH', 'admin'),
        'email' => env('ADMIN_EMAIL'),
        'password_hash' => env('ADMIN_PASSWORD_HASH'),
        'name' => env('ADMIN_NAME', 'Developer'),
        // CSV из IP и/или CIDR-блоков; пустое значение = allow-list отключён.
        // Пример: ADMIN_ALLOWED_IPS="203.0.113.5,198.51.100.0/24"
        'allowed_ips' => env('ADMIN_ALLOWED_IPS'),
        // Лимит запросов в админку: <attempts>,<minutes>. По умолчанию 60 в минуту.
        'throttle' => env('THROTTLE_ADMIN', '60,1'),
    ],

];
