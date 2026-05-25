<?php

declare(strict_types=1);

use App\Support\IpHasher;
use App\Support\TenantResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

it('хеширует IP через ключ приложения', function (): void {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

    $hash = IpHasher::hash('127.0.0.1');

    expect($hash)->not->toBeNull()
        ->and($hash)->toHaveLength(64)
        ->and(IpHasher::hash('127.0.0.1'))->toBe($hash);
});

it('возвращает null для пустого IP', function (): void {
    expect(IpHasher::hash(null))->toBeNull()
        ->and(IpHasher::hash(''))->toBeNull();
});

it('извлекает адрес из поддомена', function (): void {
    config(['guardreviews.domain' => 'guardreviews.test']);

    $request = Request::create('https://cafe.guardreviews.test/api/public/places');

    expect((new TenantResolver)->resolveSlug($request))->toBe('cafe');
});

it('читает адрес из заголовка в режиме тестирования', function (): void {
    config(['guardreviews.domain' => 'guardreviews.test']);

    $request = Request::create('https://guardreviews.test/api/public/places');
    $request->headers->set('X-Tenant-Slug', 'header-slug');

    expect((new TenantResolver)->resolveSlug($request))->toBe('header-slug');
});

it('возвращает null без контекста арендатора', function (): void {
    config(['guardreviews.domain' => 'guardreviews.test']);

    $request = Request::create('https://guardreviews.test/api/public/places');

    expect((new TenantResolver)->resolveSlug($request))->toBeNull();
});
