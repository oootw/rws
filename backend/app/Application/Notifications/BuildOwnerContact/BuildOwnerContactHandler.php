<?php

declare(strict_types=1);

namespace App\Application\Notifications\BuildOwnerContact;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsHandler;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsQuery;
use App\Application\Iam\ListOwnerTelegramChats\OwnerTelegramChatView;
use App\Application\Iam\ListPushSubscriptionsForOwner\ListPushSubscriptionsForOwnerHandler;
use App\Application\Iam\ListPushSubscriptionsForOwner\ListPushSubscriptionsForOwnerQuery;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Notifications\OwnerContact;

/**
 * Единая точка сборки OwnerContact для каналов уведомлений.
 *
 * Объединяет базовые каналы из Owner-агрегата (telegramId, maxId, email)
 * c push-подписками и групповыми Telegram-чатами владельца. Job'ам не нужно
 * знать про каждый источник — только про этот handler.
 */
final readonly class BuildOwnerContactHandler
{
    public function __construct(
        private OwnerRepository $owners,
        private ListPushSubscriptionsForOwnerHandler $pushSubscriptions,
        private ListOwnerTelegramChatsHandler $telegramChats,
    ) {}

    public function handle(BuildOwnerContactQuery $query): OwnerContact
    {
        $ownerId = new OwnerId($query->ownerId);
        $owner = $this->owners->findById($ownerId);

        if ($owner === null) {
            throw new TenantNotFound;
        }

        $base = $owner->asNotificationContact();

        return new OwnerContact(
            telegramId: $base->telegramId,
            maxId: $base->maxId,
            email: $base->email,
            ownerId: $base->ownerId,
            pushSubscriptions: $this->pushSubscriptions->handle(
                new ListPushSubscriptionsForOwnerQuery($ownerId->value),
            ),
            telegramChatIds: array_map(
                static fn (OwnerTelegramChatView $chat): string => $chat->chatId,
                $this->telegramChats->handle(new ListOwnerTelegramChatsQuery($ownerId->value)),
            ),
        );
    }
}
