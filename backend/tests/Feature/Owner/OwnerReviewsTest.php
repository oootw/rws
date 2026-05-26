<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('возвращает страницу отзывов владельца', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    Review::factory()->count(3)->create(['place_id' => $place->id]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/reviews', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.page', 1);
});

it('фильтрует по статусу', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    Review::factory()->create(['place_id' => $place->id, 'status' => ReviewStatus::New]);
    Review::factory()->create(['place_id' => $place->id, 'status' => ReviewStatus::Resolved]);
    Review::factory()->create(['place_id' => $place->id, 'status' => ReviewStatus::Resolved]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/reviews?status=resolved', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('фильтрует по point_id', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place1 = Place::factory()->create(['user_id' => $owner->id]);
    $place2 = Place::factory()->create(['user_id' => $owner->id]);
    Review::factory()->count(2)->create(['place_id' => $place1->id]);
    Review::factory()->count(1)->create(['place_id' => $place2->id]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/reviews?place_id='.$place1->id, tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('изолирует отзывы между владельцами', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    $alicePlace = Place::factory()->create(['user_id' => $alice->id]);
    $bobPlace = Place::factory()->create(['user_id' => $bob->id]);
    Review::factory()->create(['place_id' => $alicePlace->id]);
    Review::factory()->count(3)->create(['place_id' => $bobPlace->id]);

    loginAsOwner($alice);

    $this->getJson('/api/owner/reviews', tenantHeaders($alice))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('пагинация работает', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    Review::factory()->count(5)->create(['place_id' => $place->id]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/reviews?per_page=2&page=2', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.last_page', 3);
});

it('валидирует статус', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->getJson('/api/owner/reviews?status=invalid', tenantHeaders($owner))->assertStatus(422);
});
