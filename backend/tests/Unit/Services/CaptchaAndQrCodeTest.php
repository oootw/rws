<?php

declare(strict_types=1);

use App\Services\Captcha\YandexSmartCaptchaVerifier;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'guardreviews.captcha.server_key' => 'test-secret',
        'guardreviews.founder.telegram_username' => '@founder',
    ]);
});

it('принимает валидный токен Yandex SmartCaptcha', function (): void {
    Http::fake([
        'https://smartcaptcha.yandexcloud.net/validate' => Http::response(['status' => 'ok']),
    ]);

    $valid = (new YandexSmartCaptchaVerifier)->verify('token-123', '127.0.0.1');

    expect($valid)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request['secret'] === 'test-secret'
        && $request['token'] === 'token-123'
        && $request['ip'] === '127.0.0.1');
});

it('отклоняет captcha без серверного ключа', function (): void {
    config(['guardreviews.captcha.server_key' => null]);

    expect((new YandexSmartCaptchaVerifier)->verify('token'))->toBeFalse();
});

it('отклоняет captcha при ошибке API', function (): void {
    Http::fake([
        'https://smartcaptcha.yandexcloud.net/validate' => Http::response(['status' => 'failed'], 500),
    ]);

    expect((new YandexSmartCaptchaVerifier)->verify('token'))->toBeFalse();
});

it('строит URL сканирования для точки', function (): void {
    $service = new QrCodeService;
    $place = activePlace();

    $url = $service->placeScanUrl('https://demo.guardreviews.test/', $place);

    expect($url)->toBe('https://demo.guardreviews.test/s/11111111-1111-1111-1111-111111111111');
});

it('генерирует png qr-код', function (): void {
    $service = new QrCodeService;
    $url = $service->placeScanUrl('https://demo.guardreviews.test/', activePlace());

    expect($service->pngBytes($url))->toStartWith("\x89PNG\r\n\x1a\n");
})->skip(fn (): bool => ! extension_loaded('gd'), 'GD extension не установлен');

it('строит ссылку на заказ дизайна QR', function (): void {
    $url = (new QrCodeService)->designOrderUrl(activePlace());

    expect($url)->toBe('https://t.me/founder?start=design_11111111-1111-1111-1111-111111111111');
});

it('не возвращает ссылку на дизайн без имени пользователя основателя', function (): void {
    config(['guardreviews.founder.telegram_username' => '']);

    expect((new QrCodeService)->designOrderUrl(activePlace()))->toBeNull();
});
