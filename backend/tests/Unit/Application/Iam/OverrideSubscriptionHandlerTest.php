<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\OverrideSubscription\OverrideSubscriptionCommand;
use App\Application\Iam\OverrideSubscription\OverrideSubscriptionHandler;

it('выставляет произвольную дату окончания подписки', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);
    $endsAt = new DateTimeImmutable('2027-01-01T00:00:00Z');

    $updated = (new OverrideSubscriptionHandler($owners))->handle(
        new OverrideSubscriptionCommand(ownerId: $owner->id->value, endsAt: $endsAt),
    );

    expect($updated->subscription()->endsAt?->format('Y-m-d'))->toBe('2027-01-01')
        ->and($owners->owners[0]->subscription()->endsAt?->format('Y-m-d'))->toBe('2027-01-01');
});

it('сбрасывает подписку при endsAt = null', function (): void {
    $owner = restoredOwner();
    $owner->extendSubscription(30, new DateTimeImmutable('2026-06-01'));
    $owners = fakeOwnerRepository([$owner]);

    (new OverrideSubscriptionHandler($owners))->handle(
        new OverrideSubscriptionCommand(ownerId: $owner->id->value, endsAt: null),
    );

    expect($owners->owners[0]->subscription()->endsAt)->toBeNull();
});

it('бросает TenantNotFound для неизвестного владельца', function (): void {
    (new OverrideSubscriptionHandler(fakeOwnerRepository()))->handle(
        new OverrideSubscriptionCommand(
            ownerId: '00000000-0000-0000-0000-000000000000',
            endsAt: null,
        ),
    );
})->throws(TenantNotFound::class);
