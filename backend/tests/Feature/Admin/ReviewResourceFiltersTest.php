<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\Place;
use App\Models\Review;
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
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});

it('SelectFilter по статусу показывает только нужные отзывы', function (): void {
    $newReview = Review::factory()->create(['status' => ReviewStatus::New]);
    $resolved = Review::factory()->create(['status' => ReviewStatus::Resolved]);

    Livewire::test(ListReviews::class)
        ->assertCanSeeTableRecords([$newReview, $resolved])
        ->filterTable('status', ReviewStatus::New->value)
        ->assertCanSeeTableRecords([$newReview])
        ->assertCanNotSeeTableRecords([$resolved]);
});

it('SelectFilter по точке оставляет только её отзывы', function (): void {
    $place1 = Place::factory()->create();
    $place2 = Place::factory()->create();

    $r1 = Review::factory()->for($place1)->create();
    $r2 = Review::factory()->for($place2)->create();

    Livewire::test(ListReviews::class)
        ->filterTable('place_id', $place1->id)
        ->assertCanSeeTableRecords([$r1])
        ->assertCanNotSeeTableRecords([$r2]);
});

it('SelectFilter по владельцу выбирает отзывы по всем его точкам', function (): void {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();

    $placeA = Place::factory()->for($ownerA)->create();
    $placeB = Place::factory()->for($ownerB)->create();

    $reviewA = Review::factory()->for($placeA)->create();
    $reviewB = Review::factory()->for($placeB)->create();

    Livewire::test(ListReviews::class)
        ->filterTable('owner_id', $ownerA->id)
        ->assertCanSeeTableRecords([$reviewA])
        ->assertCanNotSeeTableRecords([$reviewB]);
});

it('фильтр по периоду отрезает отзывы вне диапазона', function (): void {
    $old = Review::factory()->create(['created_at' => now()->subMonth()]);
    $recent = Review::factory()->create(['created_at' => now()->subDay()]);

    Livewire::test(ListReviews::class)
        ->filterTable('created_at', [
            'from' => now()->subWeek()->toDateString(),
            'until' => now()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

it('таблица показывает ключевые поля', function (): void {
    $place = Place::factory()->create(['title' => 'PlaceColumn']);
    Review::factory()->for($place)->create(['text' => 'ВидимыйТекстОтзыва', 'contact' => 'reviewer@x.io']);

    Livewire::test(ListReviews::class)
        ->assertSeeText('ВидимыйТекстОтзыва')
        ->assertSeeText('PlaceColumn');
});
