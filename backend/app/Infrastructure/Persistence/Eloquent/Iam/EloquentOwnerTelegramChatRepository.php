<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\OwnerTelegramChatRepository;
use App\Domain\Iam\TelegramChatId;
use App\Models\OwnerTelegramChat as OwnerTelegramChatModel;

final readonly class EloquentOwnerTelegramChatRepository implements OwnerTelegramChatRepository
{
    public function __construct(private OwnerTelegramChatMapper $mapper) {}

    public function save(OwnerTelegramChat $chat): void
    {
        $model = OwnerTelegramChatModel::query()->find($chat->id->value) ?? new OwnerTelegramChatModel;

        $this->mapper->toPersistence($chat, $model)->save();
    }

    public function findById(OwnerTelegramChatId $id): ?OwnerTelegramChat
    {
        $model = OwnerTelegramChatModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByOwnerAndChat(OwnerId $ownerId, TelegramChatId $chatId): ?OwnerTelegramChat
    {
        $model = OwnerTelegramChatModel::query()
            ->where('owner_id', $ownerId->value)
            ->where('chat_id', $chatId->value)
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function listByOwner(OwnerId $ownerId): array
    {
        return OwnerTelegramChatModel::query()
            ->where('owner_id', $ownerId->value)
            ->orderBy('linked_at')
            ->get()
            ->map(fn (OwnerTelegramChatModel $model): OwnerTelegramChat => $this->mapper->toDomain($model))
            ->values()
            ->all();
    }

    public function delete(OwnerTelegramChatId $id): void
    {
        OwnerTelegramChatModel::query()->whereKey($id->value)->delete();
    }
}
