<?php

declare(strict_types=1);

use App\Application\Payments\AcquirerGateway;
use App\Application\Payments\InitPaymentResponse;
use App\Domain\Payments\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Models\Place;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('возвращает сводку подписки с тарифом и точками', function (): void {
    $tariff = Tariff::factory()->create([
        'title' => 'Pro',
        'price' => 99_000,
        'extra_place_price' => 30_000,
        'places_limit' => 5,
    ]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
        'subscription_ends_at' => Carbon::now()->addDays(15),
    ]);
    Place::factory()->count(2)->create(['user_id' => $owner->id]);

    loginAsOwner($owner);

    $response = $this->getJson('/api/owner/subscription', tenantHeaders($owner))
        ->assertOk()
        ->json('data');

    expect($response['tariff_id'])->toBe($tariff->id)
        ->and($response['tariff_title'])->toBe('Pro')
        ->and($response['is_active'])->toBeTrue()
        ->and($response['days_left'])->toBeGreaterThanOrEqual(14)
        ->and($response['places_used'])->toBe(2)
        ->and($response['places_limit'])->toBe(5)
        ->and($response['next_charge_amount'])->toBe(99_000 + 30_000);
});

it('помечает подписку как неактивную если срок истёк', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => Carbon::now()->subDay(),
    ]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/subscription', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.days_left', 0);
});

it('выдаёт payment_url при успехе эквайера', function (): void {
    $this->app->bind(AcquirerGateway::class, fn () => fakeAcquirerGateway(
        response: InitPaymentResponse::success('https://pay.test/abc', 'ext-1'),
    ));

    $tariff = Tariff::factory()->create(['price' => 99_000]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);

    loginAsOwner($owner);

    $this->postJson('/api/owner/subscription/init-payment', [], tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.payment_url', 'https://pay.test/abc');

    expect(PaymentTransaction::query()->where('user_id', $owner->id)->count())->toBe(1);
});

it('возвращает 422 если эквайер не сконфигурирован', function (): void {
    $this->app->bind(AcquirerGateway::class, fn () => fakeAcquirerGateway(configured: false));

    $tariff = Tariff::factory()->create();
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);

    loginAsOwner($owner);

    $this->postJson('/api/owner/subscription/init-payment', [], tenantHeaders($owner))
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

it('возвращает историю платежей владельца с пагинацией', function (): void {
    $tariff = Tariff::factory()->create();
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);

    PaymentTransaction::factory()->count(3)->create([
        'user_id' => $owner->id,
        'tariff_id' => $tariff->id,
        'status' => PaymentStatus::Success,
    ]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/payments', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 3);
});

it('изолирует историю платежей между владельцами', function (): void {
    $tariff = Tariff::factory()->create();
    $alice = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
    ]);
    $bob = User::factory()->create(['subdomain_slug' => 'bar', 'tariff_id' => $tariff->id]);

    PaymentTransaction::factory()->create(['user_id' => $alice->id, 'tariff_id' => $tariff->id]);
    PaymentTransaction::factory()->count(2)->create(['user_id' => $bob->id, 'tariff_id' => $tariff->id]);

    loginAsOwner($alice);

    $this->getJson('/api/owner/payments', tenantHeaders($alice))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('требует авторизации для subscription и payments', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);

    $this->getJson('/api/owner/subscription', tenantHeaders($owner))->assertStatus(401);
    $this->getJson('/api/owner/payments', tenantHeaders($owner))->assertStatus(401);
    $this->postJson('/api/owner/subscription/init-payment', [], tenantHeaders($owner))->assertStatus(401);
});
