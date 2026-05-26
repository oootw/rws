<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\TelegramId;
use App\Models\OwnerLoginRequest as OwnerLoginRequestModel;
use DateTimeImmutable;

final class OwnerLoginRequestMapper
{
    public function toDomain(OwnerLoginRequestModel $model): OwnerLoginRequest
    {
        return OwnerLoginRequest::restore(
            id: new OwnerLoginRequestId((string) $model->id),
            ownerId: new OwnerId((string) $model->owner_id),
            telegramId: new TelegramId((string) $model->telegram_id),
            code: (string) $model->code,
            expiresAt: DateTimeImmutable::createFromInterface($model->expires_at),
            consumedAt: $model->consumed_at !== null
                ? DateTimeImmutable::createFromInterface($model->consumed_at)
                : null,
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
        );
    }

    public function toPersistence(
        OwnerLoginRequest $request,
        OwnerLoginRequestModel $model,
    ): OwnerLoginRequestModel {
        $model->id = $request->id->value;
        $model->owner_id = $request->ownerId->value;
        $model->telegram_id = $request->telegramId->value;
        $model->code = $request->code;
        $model->expires_at = $request->expiresAt;
        $model->consumed_at = $request->consumedAt();
        $model->created_at = $request->createdAt;

        return $model;
    }
}
