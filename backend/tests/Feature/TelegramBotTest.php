<?php

use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewCommand;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Notifications\OwnerContact;
use App\Enums\ReviewStatus;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User as TelegramUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->forgetInstance(Nutgram::class);
    $this->app->forgetInstance('nutgram');
    $this->app->forgetInstance('telegram');
});

function fakeTelegramUser(int $id = 1001): TelegramUser
{
    return TelegramUser::make(
        id: $id,
        is_bot: false,
        first_name: 'Иван',
    );
}

function fakeTelegramChat(int $id = 1001): Chat
{
    return Chat::make(
        id: $id,
        type: 'private',
    );
}

function telegramBot(): Nutgram
{
    return app(Nutgram::class);
}

it('validates subdomain slug rules', function (): void {
    expect(fn () => new SubdomainSlug('ab'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new SubdomainSlug('api'))->toThrow(InvalidArgumentException::class);
    expect((new SubdomainSlug('valid-name'))->value)->toBe('valid-name');
});

it('completes onboarding and creates owner', function (): void {
    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser())
        ->setCommonChat(fakeTelegramChat());

    $bot->hearMessage(['text' => '/start']);
    $bot->run();

    $bot->hearText('owner@example.com');
    $bot->run();

    $bot->hearText('my-cafe');
    $bot->run();

    $owner = User::query()->where('telegram_id', '1001')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->email)->toBe('owner@example.com')
        ->and($owner->subdomain_slug)->toBe('my-cafe');
});

it('updates review status from callback', function (): void {
    $owner = User::factory()->create(['telegram_id' => '2002']);
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 2,
        'contact' => '+79990001122',
        'text' => 'Плохо',
        'status' => ReviewStatus::New,
    ]);

    $bot = telegramBot()
        ->setCommonUser(TelegramUser::make(id: 2002, is_bot: false, first_name: 'Owner'))
        ->setCommonChat(fakeTelegramChat(2002));

    $bot->hearCallbackQueryData("review:{$review->id}:in_progress");
    $bot->run();

    expect($review->fresh()->status)->toBe(ReviewStatus::InProgress);
});

it('sends negative review alert to telegram', function (): void {
    config(['nutgram.token' => '123456:TEST']);

    $owner = User::factory()->create(['telegram_id' => '3003']);
    $place = Place::factory()->for($owner)->create(['title' => 'Кафе']);
    $review = Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 1,
        'contact' => 'test@mail.ru',
        'text' => 'Ужас',
    ]);

    $bot = telegramBot();

    app(NotifyAboutNegativeReviewHandler::class)->handle(new NotifyAboutNegativeReviewCommand(
        contact: new OwnerContact(
            telegramId: (string) $owner->telegram_id,
            maxId: null,
            email: null,
        ),
        reviewId: (string) $review->id,
        placeTitle: (string) $place->title,
        stars: (int) $review->stars,
        reviewText: (string) $review->text,
        reviewerContact: (string) $review->contact,
    ));

    $bot->assertCalled('sendMessage');
});
