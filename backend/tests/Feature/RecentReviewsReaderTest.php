<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\Reviews\EloquentRecentReviewsReader;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('возвращает последние отзывы владельца через ридер на Eloquent', function (): void {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $place = Place::factory()->for($owner)->create(['title' => 'Кафе Уют']);
    $foreignPlace = Place::factory()->for($otherOwner)->create(['title' => 'Чужое']);

    Review::factory()->for($place)->create([
        'stars' => 2,
        'text' => 'Долго ждали',
        'contact' => 'test@example.com',
    ]);
    Review::factory()->for($foreignPlace)->create([
        'stars' => 1,
        'text' => 'Не должно попасть',
        'contact' => 'other@example.com',
    ]);

    $views = (new EloquentRecentReviewsReader)->recentForOwner((string) $owner->id, 10);

    expect($views)->toHaveCount(1)
        ->and($views[0]->placeTitle)->toBe('Кафе Уют')
        ->and($views[0]->stars)->toBe(2)
        ->and($views[0]->contact)->toBe('test@example.com')
        ->and($views[0]->text)->toBe('Долго ждали');
});
