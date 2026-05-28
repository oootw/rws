<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\PushSubscriptionNotFound;
use App\Application\Iam\UnregisterPushSubscription\UnregisterPushSubscriptionCommand;
use App\Application\Iam\UnregisterPushSubscription\UnregisterPushSubscriptionHandler;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\PushSubscriptionEndpoint;

function existingSubscription(string $ownerId, string $endpoint): OwnerPushSubscription
{
    return OwnerPushSubscription::register(
        id: new OwnerPushSubscriptionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: new OwnerId($ownerId),
        endpoint: new PushSubscriptionEndpoint($endpoint),
        p256dh: 'p256',
        auth: 'auth',
        userAgent: null,
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );
}

it('удаляет подписку текущего owner-а', function (): void {
    $repo = fakePushSubscriptionRepository([
        existingSubscription('11111111-1111-1111-1111-111111111111', 'https://x/y'),
    ]);

    (new UnregisterPushSubscriptionHandler($repo))->handle(new UnregisterPushSubscriptionCommand(
        ownerId: '11111111-1111-1111-1111-111111111111',
        endpoint: 'https://x/y',
    ));

    expect($repo->subscriptions)->toBe([]);
});

it('бросает NotFound на чужой endpoint и не удаляет его', function (): void {
    $repo = fakePushSubscriptionRepository([
        existingSubscription('11111111-1111-1111-1111-111111111111', 'https://x/y'),
    ]);

    try {
        (new UnregisterPushSubscriptionHandler($repo))->handle(new UnregisterPushSubscriptionCommand(
            ownerId: '22222222-2222-2222-2222-222222222222',
            endpoint: 'https://x/y',
        ));
        expect(true)->toBeFalse('Should have thrown');
    } catch (PushSubscriptionNotFound) {
        expect($repo->subscriptions)->toHaveCount(1);
    }
});

it('бросает NotFound на отсутствующий endpoint', function (): void {
    (new UnregisterPushSubscriptionHandler(fakePushSubscriptionRepository()))->handle(
        new UnregisterPushSubscriptionCommand(
            ownerId: '11111111-1111-1111-1111-111111111111',
            endpoint: 'https://x/y',
        ),
    );
})->throws(PushSubscriptionNotFound::class);
