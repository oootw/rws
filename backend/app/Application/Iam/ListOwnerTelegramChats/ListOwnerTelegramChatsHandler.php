<?php

declare(strict_types=1);

namespace App\Application\Iam\ListOwnerTelegramChats;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatRepository;

/**
 * Reader-handler: возвращает привязанные групповые TG-чаты владельца.
 * Используется и Owner-панелью (GET /telegram-chats), и сборкой
 * OwnerContact для каналов уведомлений.
 */
final readonly class ListOwnerTelegramChatsHandler
{
    public function __construct(
        private OwnerTelegramChatRepository $chats,
    ) {}

    /**
     * @return list<OwnerTelegramChatView>
     */
    public function handle(ListOwnerTelegramChatsQuery $query): array
    {
        return array_map(
            static fn (OwnerTelegramChat $chat): OwnerTelegramChatView => new OwnerTelegramChatView(
                id: $chat->id->value,
                chatId: $chat->chatId->value,
                title: $chat->title(),
                linkedAt: $chat->linkedAt,
            ),
            $this->chats->listByOwner(new OwnerId($query->ownerId)),
        );
    }
}
