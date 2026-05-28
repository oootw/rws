<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerTelegramChatRepository
{
    public function save(OwnerTelegramChat $chat): void;

    public function findById(OwnerTelegramChatId $id): ?OwnerTelegramChat;

    public function findByOwnerAndChat(OwnerId $ownerId, TelegramChatId $chatId): ?OwnerTelegramChat;

    /**
     * @return list<OwnerTelegramChat>
     */
    public function listByOwner(OwnerId $ownerId): array;

    public function delete(OwnerTelegramChatId $id): void;
}
