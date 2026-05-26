<?php

declare(strict_types=1);

use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusCommand;
use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusHandler;
use App\Application\Reviews\DeleteReview\DeleteReviewCommand;
use App\Application\Reviews\DeleteReview\DeleteReviewHandler;
use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Application\Reviews\ResendNegativeReviewAlert\ResendNegativeReviewAlertCommand;
use App\Application\Reviews\ResendNegativeReviewAlert\ResendNegativeReviewAlertHandler;
use App\Domain\Analytics\ActionType;
use App\Enums\ReviewStatus;
use App\Interface\Filament\Auth\AdminUser;
use App\Jobs\SendNegativeReviewAlert;
use App\Models\ActionLog;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
        'guardreviews.admin.name' => 'Test Dev',
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Test Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );
});

// — HTTP smoke на страницы ресурса —

it('показывает список отзывов', function (): void {
    Review::factory()->count(3)->sequence(
        ['text' => 'Альфа-фидбэк'],
        ['text' => 'Бета-фидбэк'],
        ['text' => 'Гамма-фидбэк'],
    )->create();

    $this->get('/admin/reviews')
        ->assertOk()
        ->assertSee('Альфа-фидбэк')
        ->assertSee('Бета-фидбэк')
        ->assertSee('Гамма-фидбэк');
});

it('не выставляет страницу создания отзыва', function (): void {
    $this->get('/admin/reviews/create')->assertNotFound();
});

it('открывает карточку отзыва', function (): void {
    $review = Review::factory()->create(['text' => 'Карточка отзыва']);

    $this->get("/admin/reviews/{$review->id}")
        ->assertOk()
        ->assertSee('Карточка отзыва');
});

it('открывает форму редактирования (только статус)', function (): void {
    $review = Review::factory()->create(['text' => 'Editable feedback']);

    $this->get("/admin/reviews/{$review->id}/edit")->assertOk();
});

// — Интеграция use cases поверх Eloquent —

it('AdminChangeReviewStatusHandler меняет статус в БД', function (): void {
    $review = Review::factory()->create(['status' => ReviewStatus::New]);

    app(AdminChangeReviewStatusHandler::class)->handle(new AdminChangeReviewStatusCommand(
        reviewId: (string) $review->id,
        newStatus: ReviewStatus::Resolved,
    ));

    expect($review->refresh()->status)->toBe(ReviewStatus::Resolved);
});

it('AdminChangeReviewStatusHandler бросает ReviewNotFound для неизвестного id', function (): void {
    expect(fn () => app(AdminChangeReviewStatusHandler::class)->handle(
        new AdminChangeReviewStatusCommand(
            reviewId: '00000000-0000-0000-0000-000000000000',
            newStatus: ReviewStatus::Archived,
        ),
    ))->toThrow(ReviewNotFound::class);
});

it('DeleteReviewHandler удаляет отзыв и пишет AdminDeletedReview', function (): void {
    $user = User::factory()->create();
    $place = Place::factory()->for($user)->create();
    $review = Review::factory()->for($place)->create(['stars' => 2]);

    app(DeleteReviewHandler::class)->handle(
        new DeleteReviewCommand(reviewId: (string) $review->id, reason: 'спам-бот'),
    );

    expect(Review::query()->whereKey($review->id)->exists())->toBeFalse();

    $log = ActionLog::query()
        ->where('place_id', $place->id)
        ->where('action_type', ActionType::AdminDeletedReview->value)
        ->firstOrFail();

    expect($log->metadata)->toBe([
        'review_id' => (string) $review->id,
        'stars' => 2,
        'reason' => 'спам-бот',
    ]);
});

it('DeleteReviewHandler идемпотентен: повтор бросает ReviewNotFound', function (): void {
    $review = Review::factory()->create();
    $handler = app(DeleteReviewHandler::class);

    $handler->handle(new DeleteReviewCommand(reviewId: (string) $review->id));

    expect(fn () => $handler->handle(new DeleteReviewCommand(reviewId: (string) $review->id)))
        ->toThrow(ReviewNotFound::class);
});

it('ResendNegativeReviewAlertHandler ставит SendNegativeReviewAlert в очередь', function (): void {
    Queue::fake();

    $review = Review::factory()->create();

    app(ResendNegativeReviewAlertHandler::class)->handle(
        new ResendNegativeReviewAlertCommand(reviewId: (string) $review->id),
    );

    Queue::assertPushed(
        SendNegativeReviewAlert::class,
        fn (SendNegativeReviewAlert $job): bool => $job->reviewId === (string) $review->id,
    );
});

it('ResendNegativeReviewAlertHandler не диспатчит ничего для несуществующего отзыва', function (): void {
    Queue::fake();

    expect(fn () => app(ResendNegativeReviewAlertHandler::class)->handle(
        new ResendNegativeReviewAlertCommand(reviewId: '00000000-0000-0000-0000-000000000000'),
    ))->toThrow(ReviewNotFound::class);

    Queue::assertNothingPushed();
});
