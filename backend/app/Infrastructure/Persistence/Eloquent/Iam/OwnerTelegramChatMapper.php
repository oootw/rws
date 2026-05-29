<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\TelegramChatId;
use App\Models\OwnerTelegramChat as OwnerTelegramChatModel;
use DateTimeImmutable;

final class OwnerTelegramChatMapper
{
    public function toDomain(OwnerTelegramChatModel $model): OwnerTelegramChat
    {
        return OwnerTelegramChat::restore(
            id: new OwnerTelegramChatId((string) $model->id),
            ownerId: new OwnerId((string) $model->owner_id),
            chatId: new TelegramChatId((string) $model->chat_id),
            title: $model->title !== null ? (string) $model->title : null,
            linkedAt: DateTimeImmutable::createFromInterface($model->linked_at),
        );
    }

    public function toPersistence(
        OwnerTelegramChat $chat,
        OwnerTelegramChatModel $model,
    ): OwnerTelegramChatModel {
        $model->id = $chat->id->value;
        $model->owner_id = $chat->ownerId->value;
        $model->chat_id = $chat->chatId->value;
        $model->title = $chat->title();
        $model->linked_at = $chat->linkedAt;

        return $model;
    }
}
