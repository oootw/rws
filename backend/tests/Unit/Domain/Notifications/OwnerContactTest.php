<?php

declare(strict_types=1);

use App\Domain\Notifications\OwnerContact;
use App\Domain\Notifications\PushSubscriptionView;

it('hasAnyChannel=false если нет ни одного канала', function (): void {
    expect((new OwnerContact(null, null, null))->hasAnyChannel())->toBeFalse();
});

it('hasAnyChannel=true если есть telegram/max/email', function (): void {
    expect((new OwnerContact('tg', null, null))->hasAnyChannel())->toBeTrue()
        ->and((new OwnerContact(null, 'max', null))->hasAnyChannel())->toBeTrue()
        ->and((new OwnerContact(null, null, 'a@b.ru'))->hasAnyChannel())->toBeTrue();
});

it('по умолчанию pushSubscriptions = []', function (): void {
    $contact = new OwnerContact(null, null, null);

    expect($contact->pushSubscriptions)->toBe([])
        ->and($contact->hasPushSubscriptions())->toBeFalse();
});

it('hasPushSubscriptions=true если есть хотя бы одна подписка', function (): void {
    $contact = new OwnerContact(
        telegramId: null,
        maxId: null,
        email: null,
        ownerId: 'owner-1',
        pushSubscriptions: [new PushSubscriptionView('https://x/y', 'k', 'a')],
    );

    expect($contact->hasPushSubscriptions())->toBeTrue()
        ->and($contact->hasAnyChannel())->toBeTrue();
});

it('PushSubscriptionView отклоняет пустые поля', function (): void {
    new PushSubscriptionView('', 'k', 'a');
})->throws(InvalidArgumentException::class);

it('PushSubscriptionView отклоняет пустой p256dh', function (): void {
    new PushSubscriptionView('https://x/y', '', 'a');
})->throws(InvalidArgumentException::class);

it('PushSubscriptionView отклоняет пустой auth', function (): void {
    new PushSubscriptionView('https://x/y', 'k', '');
})->throws(InvalidArgumentException::class);
