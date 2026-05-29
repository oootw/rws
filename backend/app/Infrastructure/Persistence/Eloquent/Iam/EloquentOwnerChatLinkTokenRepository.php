<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerChatLinkToken;
use App\Domain\Iam\OwnerChatLinkTokenId;
use App\Domain\Iam\OwnerChatLinkTokenRepository;
use App\Domain\Shared\Clock\Clock;
use App\Models\OwnerChatLinkToken as OwnerChatLinkTokenModel;

final readonly class EloquentOwnerChatLinkTokenRepository implements OwnerChatLinkTokenRepository
{
    public function __construct(
        private OwnerChatLinkTokenMapper $mapper,
        private Clock $clock,
    ) {}

    public function save(OwnerChatLinkToken $token): void
    {
        $model = OwnerChatLinkTokenModel::query()->find($token->id->value) ?? new OwnerChatLinkTokenModel;

        $this->mapper->toPersistence($token, $model)->save();
    }

    public function findActiveByToken(string $token): ?OwnerChatLinkToken
    {
        $model = OwnerChatLinkTokenModel::query()
            ->where('token', $token)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', $this->clock->now())
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findById(OwnerChatLinkTokenId $id): ?OwnerChatLinkToken
    {
        $model = OwnerChatLinkTokenModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
