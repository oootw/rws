<?php

declare(strict_types=1);

use App\Application\Notifications\Channels\WebPushClient;
use App\Application\Notifications\Channels\WebPushSendResult;
use App\Application\Notifications\OwnerNotification;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\PushSubscriptionRepository;
use App\Domain\Notifications\OwnerContact;
use App\Domain\Notifications\PushSubscriptionView;
use App\Infrastructure\Notifications\Channels\WebPushDeliveryFailed;
use App\Infrastructure\Notifications\Channels\WebPushNotificationChannel;
use Illuminate\Config\Repository;
use Psr\Log\NullLogger;

/**
 * @param  array<string, WebPushSendResult>  $resultsByEndpoint
 */
function scriptedWebPushClient(array $resultsByEndpoint): WebPushClient
{
    return new class($resultsByEndpoint) implements WebPushClient
    {
        public array $calls = [];

        /** @param array<string, WebPushSendResult> $resultsByEndpoint */
        public function __construct(private array $resultsByEndpoint) {}

        public function send(PushSubscriptionView $subscription, string $payload): WebPushSendResult
        {
            $this->calls[] = ['endpoint' => $subscription->endpoint, 'payload' => $payload];

            return $this->resultsByEndpoint[$subscription->endpoint]
                ?? WebPushSendResult::failed();
        }
    };
}

function vapidConfig(bool $enabled = true): Repository
{
    return new Repository([
        'services' => ['webpush' => $enabled
            ? ['public_key' => 'pub', 'private_key' => 'priv', 'subject' => 'mailto:ops@example.com']
            : ['public_key' => '', 'private_key' => '', 'subject' => ''],
        ],
    ]);
}

function contactWithPushes(string ...$endpoints): OwnerContact
{
    return new OwnerContact(
        telegramId: null,
        maxId: null,
        email: null,
        ownerId: 'owner-1',
        pushSubscriptions: array_map(
            static fn (string $e): PushSubscriptionView => new PushSubscriptionView($e, 'p', 'a'),
            $endpoints,
        ),
    );
}

function pushNotification(OwnerContact $contact): OwnerNotification
{
    return new OwnerNotification(
        contact: $contact,
        text: 'тело',
        emailSubject: 'тема',
        actions: [],
        kind: 'negative_review',
        targetUrl: '/owner/reviews/r-1',
    );
}

it('supports() = false без VAPID-конфига', function (): void {
    $channel = new WebPushNotificationChannel(
        scriptedWebPushClient([]),
        fakePushSubscriptionRepository(),
        vapidConfig(enabled: false),
        new NullLogger,
    );

    expect($channel->supports(pushNotification(contactWithPushes('https://x/y'))))->toBeFalse();
});

it('supports() = false если у владельца нет подписок', function (): void {
    $channel = new WebPushNotificationChannel(
        scriptedWebPushClient([]),
        fakePushSubscriptionRepository(),
        vapidConfig(),
        new NullLogger,
    );

    $contact = new OwnerContact(null, null, null, 'owner-1');

    expect($channel->supports(pushNotification($contact)))->toBeFalse();
});

it('доставляет на все подписки и не пишет в репозиторий, если все ok', function (): void {
    $client = scriptedWebPushClient([
        'https://x/y/1' => WebPushSendResult::delivered(),
        'https://x/y/2' => WebPushSendResult::delivered(),
    ]);
    $repo = fakePushSubscriptionRepository();

    $channel = new WebPushNotificationChannel($client, $repo, vapidConfig(), new NullLogger);

    $channel->deliver(pushNotification(contactWithPushes('https://x/y/1', 'https://x/y/2')));

    expect($client->calls)->toHaveCount(2)
        ->and($repo->subscriptions)->toBe([]);
});

it('удаляет gone-подписки и не бросает, если живых не было', function (): void {
    $client = scriptedWebPushClient([
        'https://x/y/1' => WebPushSendResult::gone(),
        'https://x/y/2' => WebPushSendResult::gone(),
    ]);
    $repo = new class implements PushSubscriptionRepository
    {
        public array $gone = [];

        public function save(OwnerPushSubscription $subscription): void {}

        public function findByEndpoint(PushSubscriptionEndpoint $endpoint): ?OwnerPushSubscription
        {
            return null;
        }

        public function listByOwner(OwnerId $ownerId): array
        {
            return [];
        }

        public function deleteByEndpoint(PushSubscriptionEndpoint $endpoint): void {}

        public function markGone(PushSubscriptionEndpoint $endpoint): void
        {
            $this->gone[] = $endpoint->value;
        }
    };

    $channel = new WebPushNotificationChannel($client, $repo, vapidConfig(), new NullLogger);

    $channel->deliver(pushNotification(contactWithPushes('https://x/y/1', 'https://x/y/2')));

    expect($repo->gone)->toBe(['https://x/y/1', 'https://x/y/2']);
});

it('считается доставившим при mix gone + ok, gone удаляется', function (): void {
    $client = scriptedWebPushClient([
        'https://x/y/1' => WebPushSendResult::gone(),
        'https://x/y/2' => WebPushSendResult::delivered(),
    ]);
    $repo = fakePushSubscriptionRepository();

    $channel = new WebPushNotificationChannel($client, $repo, vapidConfig(), new NullLogger);

    $channel->deliver(pushNotification(contactWithPushes('https://x/y/1', 'https://x/y/2')));

    expect($client->calls)->toHaveCount(2);
});

it('бросает WebPushDeliveryFailed, если живые подписки есть, но все упали', function (): void {
    $client = scriptedWebPushClient([
        'https://x/y/1' => WebPushSendResult::failed(),
    ]);
    $channel = new WebPushNotificationChannel(
        $client,
        fakePushSubscriptionRepository(),
        vapidConfig(),
        new NullLogger,
    );

    $channel->deliver(pushNotification(contactWithPushes('https://x/y/1')));
})->throws(WebPushDeliveryFailed::class);

it('payload содержит url из targetUrl и tag = kind', function (): void {
    $client = scriptedWebPushClient(['https://x/y/1' => WebPushSendResult::delivered()]);

    (new WebPushNotificationChannel(
        $client,
        fakePushSubscriptionRepository(),
        vapidConfig(),
        new NullLogger,
    ))->deliver(pushNotification(contactWithPushes('https://x/y/1')));

    $payload = json_decode($client->calls[0]['payload'], true);

    expect($payload['url'])->toBe('/owner/reviews/r-1')
        ->and($payload['tag'])->toBe('negative_review')
        ->and($payload['kind'])->toBe('negative_review')
        ->and($payload['title'])->toBe('тема')
        ->and($payload['body'])->toBe('тело');
});
