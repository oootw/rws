<?php

declare(strict_types=1);

beforeEach(function (): void {
    config([
        'guardreviews.tls_allowed_domains' => 'guardreviews.test,staging.guardreviews.test',
        'guardreviews.domain' => 'guardreviews.test',
    ]);
});

it('разрешает apex-домен для on-demand TLS', function (): void {
    $this->get('/api/internal/tls-allow?domain=guardreviews.test')
        ->assertOk()
        ->assertSee('ok');
});

it('разрешает валидный поддомен для on-demand TLS', function (): void {
    $this->get('/api/internal/tls-allow?domain=cafe.guardreviews.test')
        ->assertOk()
        ->assertSee('ok');
});

it('отклоняет неизвестный домен для on-demand TLS', function (): void {
    $this->get('/api/internal/tls-allow?domain=evil.example.com')
        ->assertNotFound()
        ->assertSee('not allowed');
});

it('отклоняет пустой домен', function (): void {
    $this->get('/api/internal/tls-allow')
        ->assertNotFound();
});

it('показывает страницу успешной оплаты', function (): void {
    $this->get('/payment/success')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Оплата прошла успешно');
});

it('показывает страницу неуспешной оплаты', function (): void {
    $this->get('/payment/fail')
        ->assertOk()
        ->assertSee('Оплата не завершена');
});
