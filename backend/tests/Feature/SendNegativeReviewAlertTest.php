<?php

declare(strict_types=1);

use App\Application\Notifications\BuildOwnerContact\BuildOwnerContactHandler;
use App\Application\Notifications\BuildOwnerContact\BuildOwnerContactQuery;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Jobs\SendNegativeReviewAlert;
use App\Models\OwnerTelegramChat;
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
        app(BuildOwnerContactHandler::class),
        app(NotifyAboutNegativeReviewHandler::class),
    );

    app(Nutgram::class)->assertCalled('sendMessage');
});

it('игнорирует задачу если отзыв не найден', function (): void {
    (new SendNegativeReviewAlert('00000000-0000-0000-0000-000000000000'))->handle(
        app(BuildOwnerContactHandler::class),
        app(NotifyAboutNegativeReviewHandler::class),
    );

    app(Nutgram::class)->assertNoReply();
});

it('игнорирует задачу если владелец точки не найден', function (): void {
    $owner = User::factory()->create();
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->for($place)->create();

    $owners = Mockery::mock(OwnerRepository::class);
    $owners->shouldReceive('findById')
        ->once()
        ->with(Mockery::on(fn (OwnerId $id): bool => $id->value === (string) $owner->id))
        ->andReturn(null);
    app()->instance(OwnerRepository::class, $owners);

    (new SendNegativeReviewAlert((string) $review->id))->handle(
        app(BuildOwnerContactHandler::class),
        app(NotifyAboutNegativeReviewHandler::class),
    );

    app(Nutgram::class)->assertNoReply();
});

it('передаёт групповые TG-чаты владельца через BuildOwnerContact', function (): void {
    $owner = User::factory()->create(['telegram_id' => '9090']);
    $place = Place::factory()->for($owner)->create(['title' => 'Бистро']);
    $review = Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 1,
        'text' => 'Плохо',
        'contact' => 'user@example.com',
    ]);

    OwnerTelegramChat::query()->create([
        'owner_id' => $owner->id,
        'chat_id' => '-1001234567890',
        'title' => 'Команда',
        'linked_at' => now(),
    ]);

    $contact = app(BuildOwnerContactHandler::class)->handle(
        new BuildOwnerContactQuery((string) $owner->id),
    );

    expect($contact->telegramId)->toBe('9090')
        ->and($contact->telegramChatIds)->toBe(['-1001234567890'])
        ->and($contact->hasAnyTelegramTarget())->toBeTrue();
});

it('возвращает экспоненциальную задержку повтора', function (): void {
    expect((new SendNegativeReviewAlert('id'))->backoff())
        ->toBe([30, 60, 120, 300, 600, 1200, 1800, 3600, 3600])
        ->and((new SendNegativeReviewAlert('id'))->tries)->toBe(10);
});
