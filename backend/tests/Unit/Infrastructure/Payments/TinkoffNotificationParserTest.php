<?php

declare(strict_types=1);

use App\Application\Payments\Exceptions\InvalidPaymentNotification;
use App\Application\Payments\NotificationOutcome;
use App\Infrastructure\Payments\Tinkoff\TinkoffConfig;
use App\Infrastructure\Payments\Tinkoff\TinkoffNotificationParser;
use App\Infrastructure\Payments\Tinkoff\TinkoffTokenSigner;
use Illuminate\Config\Repository as ConfigRepository;
use Tests\TestCase;

uses(TestCase::class);

function tinkoffParser(array $config = []): TinkoffNotificationParser
{
    $defaults = [
        'guardreviews.tinkoff.terminal_key' => 'TestTerminal',
        'guardreviews.tinkoff.secret_key' => 'test-secret',
    ];

    return new TinkoffNotificationParser(
        new TinkoffConfig(new ConfigRepository(array_merge($defaults, $config))),
        new TinkoffTokenSigner,
    );
}

function signedPayload(array $overrides = []): array
{
    $payload = array_merge([
        'TerminalKey' => 'TestTerminal',
        'OrderId' => 'order-1',
        'Success' => true,
        'Status' => 'CONFIRMED',
        'PaymentId' => 123456,
        'Amount' => 99000,
    ], $overrides);

    $payload['Token'] = (new TinkoffTokenSigner)->sign($payload, 'test-secret');

    return $payload;
}

it('парсит подтверждённое уведомление Tinkoff', function (): void {
    $notification = tinkoffParser()->parse(signedPayload());

    expect($notification->transactionId)->toBe('order-1')
        ->and($notification->outcome)->toBe(NotificationOutcome::Confirmed)
        ->and($notification->amountMinorUnits)->toBe(99000)
        ->and($notification->externalId)->toBe('123456');
});

it('интерпретирует отклонённые статусы Tinkoff', function (): void {
    $notification = tinkoffParser()->parse(signedPayload([
        'Success' => false,
        'Status' => 'REJECTED',
    ]));

    expect($notification->outcome)->toBe(NotificationOutcome::Rejected);
});

it('интерпретирует промежуточный статус как ожидание', function (): void {
    $notification = tinkoffParser()->parse(signedPayload([
        'Success' => false,
        'Status' => 'AUTHORIZED',
    ]));

    expect($notification->outcome)->toBe(NotificationOutcome::Pending);
});

it('бросает исключение без идентификатора заказа', function (): void {
    $payload = [
        'TerminalKey' => 'TestTerminal',
        'Success' => true,
        'Status' => 'CONFIRMED',
        'PaymentId' => 123456,
        'Amount' => 99000,
    ];
    $payload['Token'] = (new TinkoffTokenSigner)->sign($payload, 'test-secret');

    tinkoffParser()->parse($payload);
})->throws(InvalidPaymentNotification::class, 'Missing OrderId.');

it('бросает исключение при неверной подписи', function (): void {
    $payload = signedPayload();
    $payload['Token'] = 'bad-token';

    tinkoffParser()->parse($payload);
})->throws(InvalidPaymentNotification::class, 'Invalid Tinkoff notification signature.');

it('бросает исключение без секретного ключа', function (): void {
    tinkoffParser(['guardreviews.tinkoff.secret_key' => null])->parse(signedPayload());
})->throws(InvalidPaymentNotification::class, 'Tinkoff secret key is not configured.');
