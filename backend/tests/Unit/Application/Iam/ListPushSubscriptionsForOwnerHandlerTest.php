<?php

declare(strict_types=1);

use App\Application\Iam\ListPushSubscriptionsForOwner\ListPushSubscriptionsForOwnerHandler;
use App\Application\Iam\ListPushSubscriptionsForOwner\ListPushSubscriptionsForOwnerQuery;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Notifications\PushSubscriptionView;

function pushSub(string $id, string $ownerId, string $endpoint): OwnerPushSubscription
{
    return OwnerPushSubscription::register(
        id: new OwnerPushSubscriptionId($id),
        ownerId: new OwnerId($ownerId),
        endpoint: new PushSubscriptionEndpoint($endpoint),
        p256dh: 'p-'.$id,
        auth: 'a-'.$id,
        userAgent: null,
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );
}

it('возвращает подписки только запрошенного owner-а в виде PushSubscriptionView', function (): void {
    $repo = fakePushSubscriptionRepository([
        pushSub('11111111-1111-1111-1111-111111111111', '99999999-9999-9999-9999-999999999999', 'https://x/y/1'),
        pushSub('22222222-2222-2222-2222-222222222222', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'https://x/y/2'),
        pushSub('33333333-3333-3333-3333-333333333333', '99999999-9999-9999-9999-999999999999', 'https://x/y/3'),
    ]);

    $result = (new ListPushSubscriptionsForOwnerHandler($repo))->handle(
        new ListPushSubscriptionsForOwnerQuery('99999999-9999-9999-9999-999999999999'),
    );

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(PushSubscriptionView::class)
        ->and($result[0]->endpoint)->toBe('https://x/y/1')
        ->and($result[1]->endpoint)->toBe('https://x/y/3');
});

it('возвращает [] если у owner-а нет подписок', function (): void {
    $result = (new ListPushSubscriptionsForOwnerHandler(fakePushSubscriptionRepository()))
        ->handle(new ListPushSubscriptionsForOwnerQuery('99999999-9999-9999-9999-999999999999'));

    expect($result)->toBe([]);
});
