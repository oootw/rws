<?php

declare(strict_types=1);

use App\Models\Place;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('создаёт точку и возвращает charge-preview', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'subscription_ends_at' => null,
    ]);
    loginAsOwner($owner);

    $payload = [
        'title' => 'Уютное кафе',
        'background_image_url' => null,
        'platforms' => [
            ['type' => '2gis', 'url' => 'https://2gis.ru/firm/test', 'label' => '2GIS'],
        ],
    ];

    $response = $this->postJson('/api/owner/places', $payload, tenantHeaders($owner))
        ->assertCreated()
        ->assertJsonStructure([
            'data' => ['id'],
            'charge' => ['prorata_amount', 'days_left', 'monthly_delta', 'requires_payment'],
        ]);

    $placeId = $response->json('data.id');

    expect(Place::query()->find($placeId)?->title)->toBe('Уютное кафе');
});

it('возвращает прорату для второй точки при активной подписке', function (): void {
    $tariff = Tariff::factory()->create(['extra_place_price' => 30_000, 'duration_days' => 30]);
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
        'tariff_id' => $tariff->id,
        'subscription_ends_at' => now()->addDays(14),
    ]);
    Place::factory()->create(['user_id' => $owner->id]);
    loginAsOwner($owner);

    $response = $this->getJson('/api/owner/places/charge-preview', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.requires_payment', true)
        ->assertJsonPath('data.monthly_delta', 30_000);

    expect((int) $response->json('data.prorata_amount'))->toBeGreaterThan(0);
});

it('обновляет точку владельца', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id, 'title' => 'Старое название']);
    loginAsOwner($owner);

    $this->patchJson('/api/owner/places/'.$place->id, [
        'title' => 'Новое название',
        'background_image_url' => null,
        'platforms' => [
            ['type' => '2gis', 'url' => 'https://2gis.ru/firm/new', 'label' => '2GIS'],
        ],
    ], tenantHeaders($owner))->assertOk();

    expect(Place::query()->find($place->id)->title)->toBe('Новое название');
});

it('запрещает обновлять чужую точку', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    $bobPlace = Place::factory()->create(['user_id' => $bob->id]);
    loginAsOwner($alice);

    $this->patchJson('/api/owner/places/'.$bobPlace->id, [
        'title' => 'Хак',
        'background_image_url' => null,
        'platforms' => [],
    ], tenantHeaders($alice))
        ->assertNotFound()
        ->assertJsonPath('code', 'place_not_found');

    expect(Place::query()->find($bobPlace->id)->title)->not->toBe('Хак');
});

it('переключает активность точки', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id, 'is_active' => true]);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places/'.$place->id.'/toggle', ['active' => false], tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect((bool) Place::query()->find($place->id)->is_active)->toBeFalse();
});

it('удаляет точку владельца', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    loginAsOwner($owner);

    $this->deleteJson('/api/owner/places/'.$place->id, [], tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    expect(Place::query()->find($place->id))->toBeNull();
});

it('запрещает удалять чужую точку', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    $bobPlace = Place::factory()->create(['user_id' => $bob->id]);
    loginAsOwner($alice);

    $this->deleteJson('/api/owner/places/'.$bobPlace->id, [], tenantHeaders($alice))
        ->assertNotFound();

    expect(Place::query()->find($bobPlace->id))->not->toBeNull();
});

it('валидирует платформы при создании', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->postJson('/api/owner/places', [
        'title' => 'X',
        'platforms' => [
            ['type' => 'invalid', 'url' => 'not-a-url', 'label' => ''],
        ],
    ], tenantHeaders($owner))->assertStatus(422);
});

it('без сессии CRUD возвращает 401', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe']);

    $this->postJson('/api/owner/places', ['title' => 'x', 'platforms' => []], tenantHeaders($owner))
        ->assertUnauthorized();
});
