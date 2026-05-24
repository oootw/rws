<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\Subscription;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TelegramId;
use App\Models\User as UserModel;
use DateTimeImmutable;

/**
 * Eloquent\User <-> доменный Owner.
 * Поля Eloquent-модели остаются в имени столбцов (subscription_ends_at, telegram_id, ...),
 * но снаружи виден только Owner.
 */
final class OwnerMapper
{
    public function toDomain(UserModel $model): Owner
    {
        return Owner::restore(
            id: new OwnerId((string) $model->id),
            name: (string) $model->name,
            email: new Email((string) $model->email),
            subdomain: new SubdomainSlug((string) $model->subdomain_slug),
            telegramId: $model->telegram_id !== null ? new TelegramId((string) $model->telegram_id) : null,
            maxId: $model->max_id !== null ? (string) $model->max_id : null,
            tariffId: $model->tariff_id !== null ? new TariffId((string) $model->tariff_id) : null,
            subscription: new Subscription(
                $model->subscription_ends_at !== null
                    ? DateTimeImmutable::createFromInterface($model->subscription_ends_at)
                    : null,
            ),
        );
    }

    public function toPersistence(Owner $owner, UserModel $model): UserModel
    {
        $model->id = $owner->id->value;
        $model->name = $owner->name();
        $model->email = $owner->email()->value;
        $model->subdomain_slug = $owner->subdomain()->value;
        $model->telegram_id = $owner->telegramId()?->value;
        $model->max_id = $owner->maxId();
        $model->tariff_id = $owner->tariffId()?->value;
        $model->subscription_ends_at = $owner->subscription()->endsAt;

        return $model;
    }
}
