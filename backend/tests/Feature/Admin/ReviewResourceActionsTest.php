<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Interface\Filament\Auth\AdminUser;
use App\Jobs\SendNegativeReviewAlert;
use App\Models\Review;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
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

it('EditReview сохраняет новый статус через AdminChangeReviewStatusHandler', function (): void {
    $review = Review::factory()->create(['status' => ReviewStatus::New]);

    Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
        ->fillForm(['status' => ReviewStatus::Resolved->value])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($review->refresh()->status)->toBe(ReviewStatus::Resolved);
});

it('EditReview delete удаляет отзыв с указанием причины', function (): void {
    $review = Review::factory()->create();

    Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
        ->callAction('delete', ['reason' => 'спам'])
        ->assertNotified();

    expect(Review::query()->whereKey($review->id)->exists())->toBeFalse();
});

it('ViewReview change_status меняет статус отзыва', function (): void {
    $review = Review::factory()->create(['status' => ReviewStatus::New]);

    Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])
        ->callAction('change_status', ['status' => ReviewStatus::InProgress->value])
        ->assertNotified();

    expect($review->refresh()->status)->toBe(ReviewStatus::InProgress);
});

it('ViewReview resend_alert ставит SendNegativeReviewAlert в очередь', function (): void {
    Queue::fake();

    $review = Review::factory()->create();

    Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])
        ->callAction('resend_alert')
        ->assertNotified();

    Queue::assertPushed(
        SendNegativeReviewAlert::class,
        fn (SendNegativeReviewAlert $job): bool => $job->reviewId === (string) $review->id,
    );
});

it('ViewReview delete удаляет отзыв без причины', function (): void {
    $review = Review::factory()->create();

    Livewire::test(ViewReview::class, ['record' => $review->getRouteKey()])
        ->callAction('delete')
        ->assertNotified();

    expect(Review::query()->whereKey($review->id)->exists())->toBeFalse();
});

it('ViewReview показывает infolist для статусов InProgress и Archived', function (): void {
    $inProgress = Review::factory()->create(['status' => ReviewStatus::InProgress, 'text' => 'InProgressReview']);
    $archived = Review::factory()->create(['status' => ReviewStatus::Archived, 'text' => 'ArchivedReview']);

    Livewire::test(ViewReview::class, ['record' => $inProgress->getRouteKey()])
        ->assertSeeText('InProgressReview')
        ->assertSeeText('В работе');

    Livewire::test(ViewReview::class, ['record' => $archived->getRouteKey()])
        ->assertSeeText('ArchivedReview')
        ->assertSeeText('Архив');
});

it('ListReviews callTableAction change_status меняет статус', function (): void {
    $review = Review::factory()->create(['status' => ReviewStatus::New]);

    Livewire::test(ListReviews::class)
        ->callTableAction('change_status', $review, ['status' => ReviewStatus::Resolved->value])
        ->assertNotified();

    expect($review->refresh()->status)->toBe(ReviewStatus::Resolved);
});

it('ListReviews callTableAction resend_alert ставит задачу в очередь', function (): void {
    Queue::fake();

    $review = Review::factory()->create();

    Livewire::test(ListReviews::class)
        ->callTableAction('resend_alert', $review)
        ->assertNotified();

    Queue::assertPushed(SendNegativeReviewAlert::class);
});

it('ListReviews callTableAction delete удаляет отзыв', function (): void {
    $review = Review::factory()->create();

    Livewire::test(ListReviews::class)
        ->callTableAction('delete', $review, ['reason' => 'дубликат'])
        ->assertNotified();

    expect(Review::query()->whereKey($review->id)->exists())->toBeFalse();
});
