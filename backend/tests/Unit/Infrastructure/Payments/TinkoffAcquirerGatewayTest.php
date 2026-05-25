<?php

declare(strict_types=1);

use App\Application\Payments\InitPaymentRequest;
use App\Domain\Iam\OwnerId;
use App\Domain\Payments\Money;
use App\Domain\Payments\PaymentTransactionId;
use App\Infrastructure\Payments\Tinkoff\TinkoffAcquirerGateway;
use App\Infrastructure\Payments\Tinkoff\TinkoffConfig;
use App\Infrastructure\Payments\Tinkoff\TinkoffTokenSigner;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

function tinkoffGateway(): TinkoffAcquirerGateway
{
    $config = new ConfigRepository([
        'guardreviews.tinkoff.terminal_key' => 'TestTerminal',
        'guardreviews.tinkoff.secret_key' => 'test-secret',
        'guardreviews.tinkoff.api_url' => 'https://securepay.tinkoff.ru/v2',
        'guardreviews.tinkoff.notification_url' => 'https://app.test/webhooks/tinkoff',
        'guardreviews.tinkoff.success_url' => 'https://app.test/payment/success',
        'guardreviews.tinkoff.fail_url' => 'https://app.test/payment/fail',
    ]);

    return new TinkoffAcquirerGateway(
        new TinkoffConfig($config),
        new TinkoffTokenSigner,
        app(HttpFactory::class),
    );
}

function initPaymentRequest(): InitPaymentRequest
{
    return new InitPaymentRequest(
        transactionId: new PaymentTransactionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        customerKey: '22222222-2222-2222-2222-222222222222',
        amount: new Money(99000),
        description: 'Подписка Guard Reviews',
    );
}

it('возвращает URL платежа при успешном Init', function (): void {
    Http::fake([
        'https://securepay.tinkoff.ru/v2/Init' => Http::response([
            'Success' => true,
            'PaymentId' => 999,
            'PaymentURL' => 'https://pay.tinkoff.test/session',
        ]),
    ]);

    $response = tinkoffGateway()->initSubscriptionPayment(initPaymentRequest());

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->paymentUrl)->toBe('https://pay.tinkoff.test/session')
        ->and($response->externalId)->toBe('999');
});

it('возвращает ошибку, если Tinkoff ответил Success=false', function (): void {
    Http::fake([
        'https://securepay.tinkoff.ru/v2/Init' => Http::response([
            'Success' => false,
            'Message' => 'Недостаточно средств',
        ]),
    ]);

    $response = tinkoffGateway()->initSubscriptionPayment(initPaymentRequest());

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->errorMessage)->toBe('Недостаточно средств');
});

it('возвращает ошибку, если Tinkoff не вернул PaymentURL', function (): void {
    Http::fake([
        'https://securepay.tinkoff.ru/v2/Init' => Http::response([
            'Success' => true,
            'PaymentId' => 999,
        ]),
    ]);

    $response = tinkoffGateway()->initSubscriptionPayment(initPaymentRequest());

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->errorMessage)->toBe('Не удалось получить ссылку на оплату.');
});

it('считает шлюз настроенным при наличии ключей', function (): void {
    expect(tinkoffGateway()->isConfigured())->toBeTrue();
});

it('считает шлюз не настроенным без ключей', function (): void {
    $gateway = new TinkoffAcquirerGateway(
        new TinkoffConfig(new ConfigRepository([
            'guardreviews.tinkoff.terminal_key' => null,
            'guardreviews.tinkoff.secret_key' => null,
            'guardreviews.tinkoff.api_url' => 'https://securepay.tinkoff.ru/v2',
        ])),
        new TinkoffTokenSigner,
        app(HttpFactory::class),
    );

    expect($gateway->isConfigured())->toBeFalse();
});
