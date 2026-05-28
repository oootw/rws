<?php

declare(strict_types=1);

use App\Application\Iam\RegisterPushSubscription\RegisterPushSubscriptionCommand;
use App\Application\Iam\RegisterPushSubscription\RegisterPushSubscriptionHandler;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\PushSubscriptionEndpoint;

const OWNER_A = '11111111-1111-1111-1111-111111111111';
const OWNER_B = '22222222-2222-2222-2222-222222222222';
const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc';

function registerHandler(
    object $repo,
    array $ids = ['aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'],
    string $now = '2026-06-01T12:00:00Z',
): RegisterPushSubscriptionHandler {
    return new RegisterPushSubscriptionHandler(
        $repo,
        fakePushSubscriptionIdGenerator($ids),
        frozenClockAt($now),
        immediateTransactionRunner(),
    );
}

it('регистрирует новый endpoint', function (): void {
    $repo = fakePushSubscriptionRepository();

    registerHandler($repo)->handle(new RegisterPushSubscriptionCommand(
        ownerId: OWNER_A,
        endpoint: ENDPOINT,
        p256dh: 'p256',
        auth: 'auth',
        userAgent: 'Chrome',
    ));

    expect($repo->subscriptions)->toHaveCount(1)
        ->and($repo->subscriptions[0]->ownerId->value)->toBe(OWNER_A)
        ->and($repo->subscriptions[0]->endpoint->value)->toBe(ENDPOINT);
});

it('обновляет lastSeenAt при повторной регистрации тем же owner-ом', function (): void {
    $repo = fakePushSubscriptionRepository([
        OwnerPushSubscription::register(
            id: new OwnerPushSubscriptionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
            ownerId: new OwnerId(OWNER_A),
            endpoint: new PushSubscriptionEndpoint(ENDPOINT),
            p256dh: 'p256',
            auth: 'auth',
            userAgent: 'Chrome',
            now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ),
    ]);

    registerHandler($repo, now: '2026-06-02T15:00:00Z')->handle(new RegisterPushSubscriptionCommand(
        ownerId: OWNER_A,
        endpoint: ENDPOINT,
        p256dh: 'p256',
        auth: 'auth',
        userAgent: 'Chrome',
    ));

    expect($repo->subscriptions)->toHaveCount(1)
        ->and($repo->subscriptions[0]->lastSeenAt()?->format('c'))->toBe('2026-06-02T15:00:00+00:00');
});

it('переписывает owner_id, когда устройство сменило хозяина', function (): void {
    $repo = fakePushSubscriptionRepository([
        OwnerPushSubscription::register(
            id: new OwnerPushSubscriptionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
            ownerId: new OwnerId(OWNER_A),
            endpoint: new PushSubscriptionEndpoint(ENDPOINT),
            p256dh: 'p256',
            auth: 'auth',
            userAgent: null,
            now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ),
    ]);

    registerHandler($repo, ids: ['bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'])
        ->handle(new RegisterPushSubscriptionCommand(
            ownerId: OWNER_B,
            endpoint: ENDPOINT,
            p256dh: 'p256-new',
            auth: 'auth-new',
            userAgent: 'Safari',
        ));

    expect($repo->subscriptions)->toHaveCount(1)
        ->and($repo->subscriptions[0]->ownerId->value)->toBe(OWNER_B)
        ->and($repo->subscriptions[0]->p256dh)->toBe('p256-new')
        ->and($repo->subscriptions[0]->id->value)->toBe('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');
});

it('отклоняет некорректный endpoint через VO', function (): void {
    registerHandler(fakePushSubscriptionRepository())->handle(new RegisterPushSubscriptionCommand(
        ownerId: OWNER_A,
        endpoint: 'http://insecure/x',
        p256dh: 'p256',
        auth: 'auth',
        userAgent: null,
    ));
})->throws(InvalidArgumentException::class);
