<?php

use App\Application\Iam\ExtendSubscription\ExtendSubscriptionCommand;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionHandler;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentCommand;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentHandler;
use App\Domain\Payments\PaymentStatus;
use App\Infrastructure\Payments\Tinkoff\TinkoffTokenSigner;
use App\Models\PaymentTransaction;
use App\Models\Tariff;
use App\Models\User;
use Database\Seeders\TariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(TariffSeeder::class);

    config([
        'guardreviews.tinkoff.terminal_key' => 'TestTerminal',
        'guardreviews.tinkoff.secret_key' => 'test-secret',
        'guardreviews.tinkoff.api_url' => 'https://securepay.tinkoff.ru/v2',
    ]);
});

function signedTinkoffNotification(array $overrides = []): array
{
    $payload = array_merge([
        'TerminalKey' => 'TestTerminal',
        'OrderId' => 'order-id',
        'Success' => true,
        'Status' => 'CONFIRMED',
        'PaymentId' => 123456,
        'Amount' => 99000,
        'ErrorCode' => '0',
    ], $overrides);

    $payload['Token'] = (new TinkoffTokenSigner)->sign($payload, 'test-secret');

    return $payload;
}

it('generates tinkoff token using official example', function (): void {
    $token = (new TinkoffTokenSigner)->sign([
        'TerminalKey' => 'MerchantTerminalKey',
        'Amount' => 19200,
        'OrderId' => '00000',
        'Description' => 'Подарочная карта на 1000 рублей',
    ], '11111111111111');

    expect($token)->toBe('72dd466f8ace0a37a1f740ce5fb78101712bc0665d91a8108c7c8a0ccd426db2');
});

it('initializes subscription payment and returns payment url', function (): void {
    Http::fake([
        'https://securepay.tinkoff.ru/v2/Init' => Http::response([
            'Success' => true,
            'PaymentId' => 999,
            'PaymentURL' => 'https://pay.tinkoff.test/session',
        ]),
    ]);

    $user = User::factory()->create();
    $tariff = Tariff::query()->where('title', 'MVP')->firstOrFail();
    $user->update(['tariff_id' => $tariff->id]);

    $result = app(InitSubscriptionPaymentHandler::class)->handle(
        new InitSubscriptionPaymentCommand(ownerId: (string) $user->id),
    );

    expect($result->paymentUrl)->toBe('https://pay.tinkoff.test/session');

    $transaction = PaymentTransaction::query()->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe(PaymentStatus::Pending)
        ->and($transaction->amount)->toBe(99000)
        ->and($transaction->external_id)->toBe('999');
});

it('extends subscription from webhook confirmation', function (): void {
    $user = User::factory()->withoutSubscription()->create();
    $tariff = Tariff::query()->where('title', 'MVP')->firstOrFail();

    $transaction = PaymentTransaction::factory()->create([
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'amount' => 99000,
        'status' => PaymentStatus::Pending,
    ]);

    $this->postJson('/api/webhooks/tinkoff', signedTinkoffNotification([
        'OrderId' => $transaction->id,
    ]))->assertOk()->assertSee('OK');

    $user->refresh();
    $transaction->refresh();

    expect($transaction->status)->toBe(PaymentStatus::Success)
        ->and($user->subscription_ends_at)->not->toBeNull()
        ->and($user->subscription_ends_at->isFuture())->toBeTrue();
});

it('rejects webhook with invalid signature', function (): void {
    $transaction = PaymentTransaction::factory()->create();

    $payload = signedTinkoffNotification(['OrderId' => $transaction->id]);
    $payload['Token'] = 'invalid';

    $this->postJson('/api/webhooks/tinkoff', $payload)
        ->assertBadRequest()
        ->assertSee('INVALID');
});

it('extends active subscription from current end date', function (): void {
    $endsAt = now()->addDays(10);
    $user = User::factory()->create(['subscription_ends_at' => $endsAt]);

    app(ExtendSubscriptionHandler::class)->handle(
        new ExtendSubscriptionCommand(ownerId: (string) $user->id),
    );

    expect($user->fresh()->subscription_ends_at->toDateString())
        ->toBe($endsAt->copy()->addDays(30)->toDateString());
});

it('returns failure when tinkoff is not configured', function (): void {
    config([
        'guardreviews.tinkoff.terminal_key' => null,
        'guardreviews.tinkoff.secret_key' => null,
    ]);

    $user = User::factory()->create();
    $result = app(InitSubscriptionPaymentHandler::class)->handle(
        new InitSubscriptionPaymentCommand(ownerId: (string) $user->id),
    );

    expect($result->paymentUrl)->toBeNull()
        ->and($result->errorMessage)->toContain('недоступна');
});
