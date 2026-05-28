<?php

declare(strict_types=1);

use App\Application\Notifications\BuildOwnerContact\BuildOwnerContactHandler;
use App\Application\Notifications\Channels\WebPushClient;
use App\Application\Notifications\Channels\WebPushSendResult;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler;
use App\Domain\Notifications\PushSubscriptionView;
use App\Jobs\SendNegativeReviewAlert;
use App\Mail\PlainTextMail;
use App\Models\OwnerPushSubscription;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use SergiX44\Nutgram\Nutgram;

uses(RefreshDatabase::class);

/**
 * @param  array<string, WebPushSendResult>  $resultsByEndpoint
 */
function bindFakeWebPushClient(array $resultsByEndpoint): object
{
    $fake = new class($resultsByEndpoint) implements WebPushClient
    {
        public array $calls = [];

        /** @param array<string, WebPushSendResult> $resultsByEndpoint */
        public function __construct(private array $resultsByEndpoint) {}

        public function send(PushSubscriptionView $subscription, string $payload): WebPushSendResult
        {
            $this->calls[] = $subscription->endpoint;

            return $this->resultsByEndpoint[$subscription->endpoint]
                ?? WebPushSendResult::failed();
        }
    };
    app()->instance(WebPushClient::class, $fake);

    return $fake;
}

function seedPushSubscription(User $owner, string $endpoint): void
{
    OwnerPushSubscription::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $owner->id,
        'endpoint' => $endpoint,
        'p256dh' => 'p256-'.$endpoint,
        'auth' => 'auth-'.$endpoint,
        'user_agent' => null,
        'created_at' => now(),
        'last_seen_at' => now(),
    ]);
}

beforeEach(function (): void {
    config([
        'services.webpush.public_key' => 'pub',
        'services.webpush.private_key' => 'priv',
        'services.webpush.subject' => 'mailto:ops@example.com',
        // Telegram отключен — чтобы push был единственным instant-каналом.
        'nutgram.token' => null,
    ]);

    $this->app->forgetInstance(Nutgram::class);
});

it('шлёт push на все активные подписки owner-а и не дёргает email', function (): void {
    Mail::fake();

    $owner = User::factory()->create([
        'telegram_id' => null,
        'email' => 'owner@example.com',
    ]);
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->create(['place_id' => $place->id]);

    seedPushSubscription($owner, 'https://fcm.googleapis.com/x/one');
    seedPushSubscription($owner, 'https://fcm.googleapis.com/x/two');

    $fake = bindFakeWebPushClient([
        'https://fcm.googleapis.com/x/one' => WebPushSendResult::delivered(),
        'https://fcm.googleapis.com/x/two' => WebPushSendResult::delivered(),
    ]);

    app(SendNegativeReviewAlert::class, ['reviewId' => (string) $review->id])
        ->handle(
            app(BuildOwnerContactHandler::class),
            app(NotifyAboutNegativeReviewHandler::class),
        );

    expect($fake->calls)->toHaveCount(2);
    Mail::assertNothingSent();
});

it('удаляет gone-подписку из БД (без throw — контракт канала «тихо»)', function (): void {
    Mail::fake();

    $owner = User::factory()->create([
        'telegram_id' => null,
        'email' => 'owner@example.com',
    ]);
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->create(['place_id' => $place->id]);

    seedPushSubscription($owner, 'https://fcm.googleapis.com/x/gone');

    bindFakeWebPushClient([
        'https://fcm.googleapis.com/x/gone' => WebPushSendResult::gone(),
    ]);

    app(SendNegativeReviewAlert::class, ['reviewId' => (string) $review->id])
        ->handle(
            app(BuildOwnerContactHandler::class),
            app(NotifyAboutNegativeReviewHandler::class),
        );

    expect(OwnerPushSubscription::query()->where('owner_id', $owner->id)->count())->toBe(0);

    // По контракту канала: all-gone — тихая неудача без throw,
    // MultiChannel считает доставку успешной, email-fallback НЕ срабатывает.
    Mail::assertNothingSent();
});

it('переходит к email-fallback, если live push-подписка упала (failed)', function (): void {
    Mail::fake();

    $owner = User::factory()->create([
        'telegram_id' => null,
        'email' => 'owner@example.com',
    ]);
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->create(['place_id' => $place->id]);

    seedPushSubscription($owner, 'https://fcm.googleapis.com/x/live');

    bindFakeWebPushClient([
        'https://fcm.googleapis.com/x/live' => WebPushSendResult::failed(),
    ]);

    app(SendNegativeReviewAlert::class, ['reviewId' => (string) $review->id])
        ->handle(
            app(BuildOwnerContactHandler::class),
            app(NotifyAboutNegativeReviewHandler::class),
        );

    // Подписка осталась (не gone), но push не доставился → throw → email-fallback.
    expect(OwnerPushSubscription::query()->where('owner_id', $owner->id)->count())->toBe(1);
    Mail::assertSent(PlainTextMail::class, fn (PlainTextMail $mail): bool => $mail->hasTo('owner@example.com'));
});
