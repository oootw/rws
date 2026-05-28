<?php

declare(strict_types=1);

namespace App\Application\Iam\UnlinkTelegramChat;

use App\Application\Iam\Exceptions\TelegramChatNotOwnedByCaller;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\OwnerTelegramChatRepository;

/**
 * Use case: владелец удаляет привязанный групповой TG-чат.
 * Owner-панель передаёт chatRowId (id строки в БД); guard «строка принадлежит
 * caller-у» бросает TelegramChatNotOwnedByCaller → HTTP-слой превращает в 404,
 * чтобы не светить факт существования чужих привязок.
 */
final readonly class UnlinkTelegramChatHandler
{
    public function __construct(
        private OwnerTelegramChatRepository $chats,
    ) {}

    public function handle(UnlinkTelegramChatCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $rowId = new OwnerTelegramChatId($command->chatRowId);

        $chat = $this->chats->findById($rowId);

        if ($chat === null || ! $chat->ownerId->equals($ownerId)) {
            throw new TelegramChatNotOwnedByCaller;
        }

        $this->chats->delete($rowId);
    }
}
