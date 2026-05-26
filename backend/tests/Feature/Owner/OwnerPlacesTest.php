<?php

declare(strict_types=1);

use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('возвращает список точек владельца', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    Place::factory()->count(2)->create(['user_id' => $owner->id]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/places', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'platforms_count', 'is_active']]]);
});

it('не показывает чужие точки', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    Place::factory()->count(1)->create(['user_id' => $alice->id]);
    Place::factory()->count(3)->create(['user_id' => $bob->id]);

    loginAsOwner($alice);

    $this->getJson('/api/owner/places', tenantHeaders($alice))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('возвращает detail с QR ссылкой', function (): void {
    config(['guardreviews.domain' => 'otziv.space']);
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Уютное кафе',
    ]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/places/'.$place->id, tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.id', $place->id)
        ->assertJsonPath('data.title', 'Уютное кафе')
        ->assertJsonPath('data.scan_url', "https://cafe.otziv.space/s/{$place->id}")
        ->assertJsonPath('data.qr_png_url', "/api/owner/places/{$place->id}/qr.png");
});

it('отдаёт 404 при попытке открыть чужую точку', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    $bobPlace = Place::factory()->create(['user_id' => $bob->id]);

    loginAsOwner($alice);

    $this->getJson('/api/owner/places/'.$bobPlace->id, tenantHeaders($alice))
        ->assertNotFound()
        ->assertJsonPath('code', 'place_not_found');
});
