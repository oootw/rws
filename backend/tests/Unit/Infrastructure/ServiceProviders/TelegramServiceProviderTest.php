<?php

declare(strict_types=1);

use App\Infrastructure\ServiceProviders\TelegramServiceProvider;
use GuzzleHttp\HandlerStack;
use Tests\TestCase;

uses(TestCase::class);

function registerTelegramServiceProvider(): void
{
    (new TelegramServiceProvider(app()))->register();
}

it('не меняет конфиг Nutgram без списка URL', function (): void {
    config([
        'guardreviews.telegram.api_urls' => '',
        'nutgram.config.api_url' => 'https://api.telegram.org',
        'nutgram.config.client' => ['timeout' => 5],
    ]);

    registerTelegramServiceProvider();

    expect(config('nutgram.config.api_url'))->toBe('https://api.telegram.org')
        ->and(config('nutgram.config.client'))->toBe(['timeout' => 5]);
});

it('устанавливает основной URL API для одного relay', function (): void {
    config([
        'guardreviews.telegram.api_urls' => 'https://relay.example.com',
        'nutgram.config.api_url' => 'https://api.telegram.org',
        'nutgram.config.client' => [],
    ]);

    registerTelegramServiceProvider();

    expect(config('nutgram.config.api_url'))->toBe('https://relay.example.com')
        ->and(config('nutgram.config.client.handler'))->toBeNull();
});

it('настраивает обработчик failover для нескольких relay URL', function (): void {
    config([
        'guardreviews.telegram.api_urls' => ' https://primary.example.com , https://backup.example.com ',
        'nutgram.config.api_url' => 'https://api.telegram.org',
        'nutgram.config.client' => ['headers' => ['X-Test' => '1']],
    ]);

    registerTelegramServiceProvider();

    expect(config('nutgram.config.api_url'))->toBe('https://primary.example.com')
        ->and(config('nutgram.config.client.handler'))->toBeInstanceOf(HandlerStack::class)
        ->and(config('nutgram.config.client.headers'))->toBe(['X-Test' => '1']);
});

it('игнорирует пустые элементы в CSV со списком URL', function (): void {
    config([
        'guardreviews.telegram.api_urls' => 'https://only.example.com,, ,',
        'nutgram.config.api_url' => 'https://api.telegram.org',
        'nutgram.config.client' => [],
    ]);

    registerTelegramServiceProvider();

    expect(config('nutgram.config.api_url'))->toBe('https://only.example.com')
        ->and(config('nutgram.config.client.handler'))->toBeNull();
});
