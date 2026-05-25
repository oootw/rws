<?php

declare(strict_types=1);

use App\Jobs\SendNegativeReviewAlert;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    resetTelegramBot();
    config(['nutgram.token' => '123456:TEST']);
});

it('доставляет алерт при выполнении задачи', function (): void {
    $owner = User::factory()->create(['telegram_id' => '9090']);
    $place = Place::factory()->for($owner)->create(['title' => 'Бистро']);
    $review = Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 1,
        'text' => 'Плохо',
        'contact' => 'user@example.com',
    ]);

    (new SendNegativeReviewAlert((string) $review->id))->handle(
        app(App\Application\Iam\GetOwnerById\GetOwnerByIdHandler::class),
        app(App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler::class),
    );

    app(Nutgram::class)->assertCalled('sendMessage');
});

it('игнорирует задачу если отзыв не найден', function (): void {
    (new SendNegativeReviewAlert('00000000-0000-0000-0000-000000000000'))->handle(
        app(App\Application\Iam\GetOwnerById\GetOwnerByIdHandler::class),
        app(App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler::class),
    );

    app(Nutgram::class)->assertNoReply();
});

it('возвращает экспоненциальную задержку повтора', function (): void {
    expect((new SendNegativeReviewAlert('id'))->backoff())
        ->toBe([30, 60, 120, 300, 600, 1200, 1800, 3600, 3600])
        ->and((new SendNegativeReviewAlert('id'))->tries)->toBe(10);
});
