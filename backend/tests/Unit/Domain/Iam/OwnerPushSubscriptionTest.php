<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\PushSubscriptionEndpoint;

function registeredPushSubscription(
    string $endpoint = 'https://fcm.googleapis.com/fcm/send/abc',
    string $p256dh = 'BPubKey',
    string $auth = 'authValue',
    ?string $userAgent = 'Chrome/120',
    string $now = '2026-06-01T12:00:00Z',
): OwnerPushSubscription {
    return OwnerPushSubscription::register(
        id: new OwnerPushSubscriptionId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        endpoint: new PushSubscriptionEndpoint($endpoint),
        p256dh: $p256dh,
        auth: $auth,
        userAgent: $userAgent,
        now: new DateTimeImmutable($now),
    );
}

it('создаётся через register c lastSeenAt = now', function (): void {
    $sub = registeredPushSubscription();

    expect($sub->createdAt->format('c'))->toBe('2026-06-01T12:00:00+00:00')
        ->and($sub->lastSeenAt()?->format('c'))->toBe('2026-06-01T12:00:00+00:00')
        ->and($sub->endpoint->value)->toBe('https://fcm.googleapis.com/fcm/send/abc')
        ->and($sub->userAgent)->toBe('Chrome/120');
});

it('бросает ошибку на пустой p256dh', function (): void {
    registeredPushSubscription(p256dh: '');
})->throws(InvalidArgumentException::class);

it('бросает ошибку на пустой auth', function (): void {
    registeredPushSubscription(auth: '');
})->throws(InvalidArgumentException::class);

it('допускает null user-agent', function (): void {
    $sub = registeredPushSubscription(userAgent: null);

    expect($sub->userAgent)->toBeNull();
});

it('бросает ошибку на слишком длинный user-agent', function (): void {
    registeredPushSubscription(userAgent: str_repeat('x', 256));
})->throws(InvalidArgumentException::class);

it('обновляет lastSeenAt через touchLastSeen', function (): void {
    $sub = registeredPushSubscription();

    $sub->touchLastSeen(new DateTimeImmutable('2026-06-02T15:30:00Z'));

    expect($sub->lastSeenAt()?->format('c'))->toBe('2026-06-02T15:30:00+00:00');
});

it('restore() не меняет lastSeenAt', function (): void {
    $sub = OwnerPushSubscription::restore(
        id: new OwnerPushSubscriptionId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        endpoint: new PushSubscriptionEndpoint('https://example.com/p/1'),
        p256dh: 'k',
        auth: 'a',
        userAgent: null,
        createdAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        lastSeenAt: null,
    );

    expect($sub->lastSeenAt())->toBeNull();
});
