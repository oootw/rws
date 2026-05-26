<?php

declare(strict_types=1);

use App\Application\Payments\Exceptions\PaymentTransactionNotFound;
use App\Application\Payments\ForceFailPayment\ForceFailPaymentCommand;
use App\Application\Payments\ForceFailPayment\ForceFailPaymentHandler;
use App\Application\Payments\HandlePaymentNotification\HandlePaymentNotificationCommand;
use App\Application\Payments\HandlePaymentNotification\HandlePaymentNotificationHandler;
use App\Domain\Payments\PaymentStatus;
use App\Filament\Resources\PaymentTransactions\Pages\ListPaymentTransactions;
use App\Filament\Resources\PaymentTransactions\Pages\ViewPaymentTransaction;
use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Infrastructure\Payments\Tinkoff\TinkoffTokenSigner;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\PaymentTransaction;
use App\Models\Tariff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
        'guardreviews.admin.name' => 'Test Dev',
        'guardreviews.tinkoff.terminal_key' => 'TestTerminal',
        'guardreviews.tinkoff.secret_key' => 'test-secret',
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Test Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});

it('показывает список платежей', function (): void {
    PaymentTransaction::factory()->count(3)->create();

    $this->get('/admin/payment-transactions')->assertOk();
});

it('не выставляет страницу создания платежа', function (): void {
    $this->get('/admin/payment-transactions/create')->assertNotFound();
    expect(PaymentTransactionResource::canCreate())->toBeFalse();
});

it('открывает карточку платежа', function (): void {
    $payment = PaymentTransaction::factory()->create();

    $this->get("/admin/payment-transactions/{$payment->id}")->assertOk();
});

// — Use case интеграция —

it('ForceFailPaymentHandler переводит pending в failed', function (): void {
    $tariff = Tariff::factory()->create();
    $user = User::factory()->withTariff($tariff)->create();
    $payment = PaymentTransaction::factory()->create([
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Pending,
    ]);

    app(ForceFailPaymentHandler::class)->handle(
        new ForceFailPaymentCommand(transactionId: (string) $payment->id),
    );

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
});

it('ForceFailPaymentHandler бросает PaymentTransactionNotFound для неизвестного id', function (): void {
    expect(fn () => app(ForceFailPaymentHandler::class)->handle(
        new ForceFailPaymentCommand(transactionId: '00000000-0000-0000-0000-000000000000'),
    ))->toThrow(PaymentTransactionNotFound::class);
});

it('refire_webhook (подписанный CONFIRMED) подтверждает транзакцию и продлевает подписку', function (): void {
    $tariff = Tariff::factory()->create();
    $user = User::factory()->withTariff($tariff)->create(['subscription_ends_at' => null]);
    $payment = PaymentTransaction::factory()->create([
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Pending,
        'amount' => 99000,
    ]);

    $payload = [
        'TerminalKey' => 'TestTerminal',
        'OrderId' => (string) $payment->id,
        'Status' => 'CONFIRMED',
        'Success' => true,
        'Amount' => 99000,
        'PaymentId' => 'admin-refire-test',
    ];
    $payload['Token'] = app(TinkoffTokenSigner::class)->sign($payload, 'test-secret');

    app(HandlePaymentNotificationHandler::class)->handle(
        new HandlePaymentNotificationCommand(payload: $payload),
    );

    expect($payment->refresh()->status)->toBe(PaymentStatus::Success)
        ->and($user->refresh()->subscription_ends_at)->not->toBeNull();
});

it('ListPaymentTransactions показывает все статусы включая refunded', function (): void {
    $tariff = Tariff::factory()->create(['title' => 'RefTariff']);
    $user = User::factory()->withTariff($tariff)->create(['name' => 'RefundUser']);

    $refunded = PaymentTransaction::factory()->create([
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Refunded,
        'amount' => 150000,
        'external_id' => 'ext-refund-1',
    ]);

    Livewire::test(ListPaymentTransactions::class)
        ->assertCanSeeTableRecords([$refunded])
        ->assertSeeText('1 500,00 ₽')
        ->assertSeeText('RefundUser')
        ->assertSeeText('RefTariff');
});

it('ListPaymentTransactions фильтрует по статусу, владельцу и тарифу', function (): void {
    $tariffA = Tariff::factory()->create(['title' => 'TariffA']);
    $tariffB = Tariff::factory()->create(['title' => 'TariffB']);
    $userA = User::factory()->withTariff($tariffA)->create();
    $userB = User::factory()->withTariff($tariffB)->create();

    $match = PaymentTransaction::factory()->create([
        'user_id' => $userA->id,
        'tariff_id' => $tariffA->id,
        'status' => PaymentStatus::Success,
    ]);
    $other = PaymentTransaction::factory()->create([
        'user_id' => $userB->id,
        'tariff_id' => $tariffB->id,
        'status' => PaymentStatus::Pending,
    ]);

    Livewire::test(ListPaymentTransactions::class)
        ->filterTable('status', PaymentStatus::Success->value)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other])
        ->filterTable('user_id', $userA->id)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other])
        ->filterTable('tariff_id', $tariffA->id)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);
});

it('ViewPaymentTransaction показывает карточку с подпиской владельца', function (): void {
    $tariff = Tariff::factory()->create(['title' => 'ViewTariff']);
    $user = User::factory()->withTariff($tariff)->create([
        'name' => 'PayOwner',
        'subscription_ends_at' => now()->addMonth(),
    ]);
    $payment = PaymentTransaction::factory()->create([
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Success,
        'amount' => 99000,
        'external_id' => 'acq-123',
    ]);

    Livewire::test(ViewPaymentTransaction::class, ['record' => $payment->getRouteKey()])
        ->assertSuccessful()
        ->assertSeeText('990,00 ₽')
        ->assertSeeText('PayOwner')
        ->assertSeeText('ViewTariff')
        ->assertSeeText('acq-123');
});

it('ViewPaymentTransaction mark_failed переводит pending в failed', function (): void {
    $payment = PaymentTransaction::factory()->create(['status' => PaymentStatus::Pending]);

    Livewire::test(ViewPaymentTransaction::class, ['record' => $payment->getRouteKey()])
        ->callAction('mark_failed')
        ->assertNotified();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
});

it('ViewPaymentTransaction refire_webhook с REJECTED помечает транзакцию failed', function (): void {
    $tariff = Tariff::factory()->create();
    $user = User::factory()->withTariff($tariff)->create();
    $payment = PaymentTransaction::factory()->create([
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Pending,
        'amount' => 99000,
        'external_id' => null,
    ]);

    Livewire::test(ViewPaymentTransaction::class, ['record' => $payment->getRouteKey()])
        ->callAction('refire_webhook', data: ['status' => 'REJECTED'])
        ->assertNotified();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
});

it('ListPaymentTransactions callTableAction mark_failed на pending транзакции', function (): void {
    $payment = PaymentTransaction::factory()->create(['status' => PaymentStatus::Pending]);

    Livewire::test(ListPaymentTransactions::class)
        ->callTableAction('mark_failed', $payment)
        ->assertNotified();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
});
