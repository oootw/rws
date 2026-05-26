<?php

declare(strict_types=1);

use App\Services\EnvMasker;

it('маскирует ключи с KEY/SECRET/TOKEN/PASSWORD/HASH/SALT/DSN', function (string $key): void {
    $masker = new EnvMasker;
    $out = $masker->mask([$key => 'super-secret']);

    expect($out[$key])->toBe('••• (length: 12)');
})->with([
    'APP_KEY', 'TINKOFF_SECRET_KEY', 'TELEGRAM_BOT_TOKEN',
    'DB_PASSWORD', 'ADMIN_PASSWORD_HASH', 'YANDEX_CAPTCHA_SALT',
    'TELEGRAM_PROXY_SECRET', 'CONNECTION_DSN',
]);

it('не маскирует безопасные ключи', function (): void {
    $out = (new EnvMasker)->mask([
        'APP_ENV' => 'production',
        'APP_URL' => 'https://otziv.space',
        'DB_HOST' => 'postgres',
    ]);

    expect($out)->toBe([
        'APP_ENV' => 'production',
        'APP_URL' => 'https://otziv.space',
        'DB_HOST' => 'postgres',
    ]);
});

it('сохраняет длину оригинала, но скрывает само значение', function (): void {
    $out = (new EnvMasker)->mask(['APP_KEY' => 'abc']);

    expect($out['APP_KEY'])->toBe('••• (length: 3)');
});
