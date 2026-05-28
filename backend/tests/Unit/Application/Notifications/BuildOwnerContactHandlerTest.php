<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsHandler;
use App\Application\Iam\ListPushSubscriptionsForOwner\ListPushSubscriptionsForOwnerHandler;
use App\Application\Notifications\BuildOwnerContact\BuildOwnerContactHandler;
use App\Application\Notifications\BuildOwnerContact\BuildOwnerContactQuery;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\TelegramChatId;

it('собирает OwnerContact с базовыми каналами, push-подписками и группами', function (): void {
    $owner = restoredOwner('11111111-1111-1111-1111-111111111111');
    $owners = fakeOwnerRepository([$owner]);

    $pushes = fakePushSubscriptionRepository([
        OwnerPushSubscription::register(
            id: new OwnerPushSubscriptionId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
            ownerId: new OwnerId('11111111-1111-1111-1111-111111111111'),
            endpoint: new PushSubscriptionEndpoint('https://x/y/1'),
            p256dh: 'p',
            auth: 'a',
            userAgent: null,
            now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ),
    ]);

    $chats = fakeOwnerTelegramChatRepository([
        OwnerTelegramChat::restore(
            id: new OwnerTelegramChatId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
            ownerId: new OwnerId('11111111-1111-1111-1111-111111111111'),
            chatId: new TelegramChatId('-1001234567890'),
            title: 'Кафе',
            linkedAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ),
    ]);

    $handler = new BuildOwnerContactHandler(
        $owners,
        new ListPushSubscriptionsForOwnerHandler($pushes),
        new ListOwnerTelegramChatsHandler($chats),
    );

    $contact = $handler->handle(new BuildOwnerContactQuery('11111111-1111-1111-1111-111111111111'));

    expect($contact->telegramId)->toBe('1001')
        ->and($contact->email)->toBe('owner@example.com')
        ->and($contact->ownerId)->toBe('11111111-1111-1111-1111-111111111111')
        ->and($contact->pushSubscriptions)->toHaveCount(1)
        ->and($contact->pushSubscriptions[0]->endpoint)->toBe('https://x/y/1')
        ->and($contact->hasPushSubscriptions())->toBeTrue()
        ->and($contact->telegramChatIds)->toBe(['-1001234567890'])
        ->and($contact->hasAnyTelegramTarget())->toBeTrue();
});

it('бросает TenantNotFound, если owner-а не существует', function (): void {
    $handler = new BuildOwnerContactHandler(
        fakeOwnerRepository(),
        new ListPushSubscriptionsForOwnerHandler(fakePushSubscriptionRepository()),
        new ListOwnerTelegramChatsHandler(fakeOwnerTelegramChatRepository()),
    );

    $handler->handle(new BuildOwnerContactQuery('00000000-0000-0000-0000-000000000000'));
})->throws(TenantNotFound::class);
