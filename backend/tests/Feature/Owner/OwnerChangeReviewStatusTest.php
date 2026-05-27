<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('меняет статус своего отзыва', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    $review = Review::factory()->create(['place_id' => $place->id, 'status' => ReviewStatus::New]);

    loginAsOwner($owner);

    $this->patchJson(
        "/api/owner/reviews/{$review->id}/status",
        ['status' => 'resolved'],
        tenantHeaders($owner)
    )
        ->assertOk()
        ->assertJsonPath('data.id', $review->id)
        ->assertJsonPath('data.status', 'resolved');

    expect(Review::query()->find($review->id)->status)->toBe(ReviewStatus::Resolved);
});

it('возвращает 404 для отзыва чужой точки', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    $bobPlace = Place::factory()->create(['user_id' => $bob->id]);
    $review = Review::factory()->create(['place_id' => $bobPlace->id, 'status' => ReviewStatus::New]);

    loginAsOwner($alice);

    $this->patchJson(
        "/api/owner/reviews/{$review->id}/status",
        ['status' => 'resolved'],
        tenantHeaders($alice)
    )
        ->assertStatus(404)
        ->assertJsonPath('code', 'review_not_found');

    expect(Review::query()->find($review->id)->status)->toBe(ReviewStatus::New);
});

it('возвращает 404 для несуществующего отзыва', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->patchJson(
        '/api/owner/reviews/00000000-0000-0000-0000-000000000000/status',
        ['status' => 'resolved'],
        tenantHeaders($owner)
    )
        ->assertStatus(404)
        ->assertJsonPath('code', 'review_not_found');
});

it('валидирует значение статуса', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    $review = Review::factory()->create(['place_id' => $place->id]);

    loginAsOwner($owner);

    $this->patchJson(
        "/api/owner/reviews/{$review->id}/status",
        ['status' => 'bogus'],
        tenantHeaders($owner)
    )->assertStatus(422);
});

it('требует авторизации', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);
    $review = Review::factory()->create(['place_id' => $place->id]);

    $this->patchJson(
        "/api/owner/reviews/{$review->id}/status",
        ['status' => 'resolved'],
        tenantHeaders($owner)
    )->assertStatus(401);
});
