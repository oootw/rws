<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerChatLinkToken;
use App\Domain\Iam\OwnerChatLinkTokenId;
use App\Domain\Iam\OwnerId;
use App\Models\OwnerChatLinkToken as OwnerChatLinkTokenModel;
use DateTimeImmutable;

final class OwnerChatLinkTokenMapper
{
    public function toDomain(OwnerChatLinkTokenModel $model): OwnerChatLinkToken
    {
        return OwnerChatLinkToken::restore(
            id: new OwnerChatLinkTokenId((string) $model->id),
            ownerId: new OwnerId((string) $model->owner_id),
            token: (string) $model->token,
            expiresAt: DateTimeImmutable::createFromInterface($model->expires_at),
            consumedAt: $model->consumed_at !== null
                ? DateTimeImmutable::createFromInterface($model->consumed_at)
                : null,
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
        );
    }

    public function toPersistence(
        OwnerChatLinkToken $token,
        OwnerChatLinkTokenModel $model,
    ): OwnerChatLinkTokenModel {
        $model->id = $token->id->value;
        $model->owner_id = $token->ownerId->value;
        $model->token = $token->token;
        $model->expires_at = $token->expiresAt;
        $model->consumed_at = $token->consumedAt();
        $model->created_at = $token->createdAt;

        return $model;
    }
}
