<?php

declare(strict_types=1);

use App\Domain\Analytics\ActionType;
use App\Models\ActionLog;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function seedActionLog(Place $place, ActionType $type, ?DateTimeImmutable $at = null): void
{
    ActionLog::query()->create([
        'id' => (string) Str::uuid(),
        'place_id' => $place->id,
        'action_type' => $type->value,
        'metadata' => null,
        'created_at' => $at ?? now(),
    ]);
}

it('возвращает KPI и серию за 7 дней', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $place = Place::factory()->create(['user_id' => $owner->id]);

    seedActionLog($place, ActionType::Scanned);
    seedActionLog($place, ActionType::Scanned);
    seedActionLog($place, ActionType::RedirectedExternal);
    seedActionLog($place, ActionType::LeftNegative);
    Review::factory()->create(['place_id' => $place->id]);
    Review::factory()->create(['place_id' => $place->id]);

    loginAsOwner($owner);

    $this->getJson('/api/owner/dashboard', tenantHeaders($owner))
        ->assertOk()
        ->assertJsonPath('data.scans', 2)
        ->assertJsonPath('data.redirects', 1)
        ->assertJsonPath('data.negative', 1)
        ->assertJsonPath('data.reviews', 2)
        ->assertJsonPath('data.places_count', 1)
        ->assertJsonCount(7, 'data.daily_series');
});

it('изолирует KPI между владельцами', function (): void {
    $alice = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    $bob = User::factory()->create(['subdomain_slug' => 'bar']);
    $alicePlace = Place::factory()->create(['user_id' => $alice->id]);
    $bobPlace = Place::factory()->create(['user_id' => $bob->id]);

    seedActionLog($alicePlace, ActionType::Scanned);
    seedActionLog($bobPlace, ActionType::Scanned);
    seedActionLog($bobPlace, ActionType::Scanned);

    loginAsOwner($alice);

    $this->getJson('/api/owner/dashboard', tenantHeaders($alice))
        ->assertOk()
        ->assertJsonPath('data.scans', 1)
        ->assertJsonPath('data.places_count', 1);
});

it('без сессии возвращает 401', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe']);

    $this->getJson('/api/owner/dashboard', tenantHeaders($owner))->assertUnauthorized();
});
