<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Models\OwnerPushSubscription as OwnerPushSubscriptionModel;
use DateTimeImmutable;

final class OwnerPushSubscriptionMapper
{
    public function toDomain(OwnerPushSubscriptionModel $model): OwnerPushSubscription
    {
        return OwnerPushSubscription::restore(
            id: new OwnerPushSubscriptionId((string) $model->id),
            ownerId: new OwnerId((string) $model->owner_id),
            endpoint: new PushSubscriptionEndpoint((string) $model->endpoint),
            p256dh: (string) $model->p256dh,
            auth: (string) $model->auth,
            userAgent: $model->user_agent === null ? null : (string) $model->user_agent,
            createdAt: DateTimeImmutable::createFromInterface($model->created_at),
            lastSeenAt: $model->last_seen_at !== null
                ? DateTimeImmutable::createFromInterface($model->last_seen_at)
                : null,
        );
    }

    public function toPersistence(
        OwnerPushSubscription $subscription,
        OwnerPushSubscriptionModel $model,
    ): OwnerPushSubscriptionModel {
        $model->id = $subscription->id->value;
        $model->owner_id = $subscription->ownerId->value;
        $model->endpoint = $subscription->endpoint->value;
        $model->p256dh = $subscription->p256dh;
        $model->auth = $subscription->auth;
        $model->user_agent = $subscription->userAgent;
        $model->created_at = $subscription->createdAt;
        $model->last_seen_at = $subscription->lastSeenAt();

        return $model;
    }
}
