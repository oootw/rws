<?php

declare(strict_types=1);

use App\Application\Payments\AcquirerGateway;
use App\Models\Place;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('возвращает 403 при cross-tenant атаке (cookie от чужого поддомена)', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar', 'telegram_id' => '2002']);
    loginAsOwner($alice);

    // Сессия залогинила Alice, но запрос идёт к поддомену Bob — middleware
    // EnsureSessionMatchesTenant обязан отказать с 403 и инвалидировать сессию.
    $this->getJson('/api/owner/me', tenantHeaders($bob))
        ->assertStatus(403)
        ->assertJsonPath('code', 'session_tenant_mismatch');
});

it('блокирует мутации точек при истёкшей подписке (402)', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => Carbon::now()->subDay(),
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places', [
        'title' => 'Точка',
        'platforms' => [['type' => 'google', 'url' => 'https://g.test/x']],
    ], tenantHeaders($owner))
        ->assertStatus(402)
        ->assertJsonPath('code', 'subscription_expired');
});

it('блокирует смену статуса отзыва при истёкшей подписке (402)', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => Carbon::now()->subDay(),
    ]);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    $review = Review::factory()->create(['place_id' => $place->id]);
    loginAsOwner($owner);

    $this->patchJson("/api/owner/reviews/{$review->id}/status", [
        'status' => 'resolved',
    ], tenantHeaders($owner))
        ->assertStatus(402)
        ->assertJsonPath('code', 'subscription_expired');
});

it('оставляет read-endpoint доступным при истёкшей подписке', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => Carbon::now()->subDay(),
    ]);
    Place::factory()->count(2)->create(['user_id' => $owner->id]);
    loginAsOwner($owner);

    $this->getJson('/api/owner/places', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/owner/subscription', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

it('пропускает init-payment даже при истёкшей подписке (продление возможно)', function (): void {
    $this->app->bind(
        AcquirerGateway::class,
        fn () => fakeAcquirerGateway(),
    );

    $tariff = Tariff::factory()->create();
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
        'subscription_ends_at' => Carbon::now()->subDay(),
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/subscription/init-payment', [], tenantHeaders($owner))
        ->assertOk()
        ->assertJsonStructure(['data' => ['payment_url']]);
});

it('пропускает PATCH /profile даже при истёкшей подписке', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => Carbon::now()->subDay(),
    ]);
    loginAsOwner($owner);

    $this->patchJson('/api/owner/profile', [
        'name' => 'Новый',
        'email' => $owner->email,
        'subdomain' => 'cafe',
    ], tenantHeaders($owner))->assertOk();
});

it('разрешает мутации точек при активной подписке', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => Carbon::now()->addDays(7),
    ]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places', [
        'title' => 'Точка',
        'platforms' => [['type' => 'google', 'url' => 'https://g.test/x']],
    ], tenantHeaders($owner))->assertStatus(201);
});
