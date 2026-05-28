<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\PushSubscriptionRepository;
use App\Models\OwnerPushSubscription as OwnerPushSubscriptionModel;

final readonly class EloquentPushSubscriptionRepository implements PushSubscriptionRepository
{
    public function __construct(private OwnerPushSubscriptionMapper $mapper) {}

    public function save(OwnerPushSubscription $subscription): void
    {
        $model = OwnerPushSubscriptionModel::query()->find($subscription->id->value)
            ?? new OwnerPushSubscriptionModel;

        $this->mapper->toPersistence($subscription, $model)->save();
    }

    public function findByEndpoint(PushSubscriptionEndpoint $endpoint): ?OwnerPushSubscription
    {
        $model = OwnerPushSubscriptionModel::query()
            ->where('endpoint', $endpoint->value)
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function listByOwner(OwnerId $ownerId): array
    {
        return OwnerPushSubscriptionModel::query()
            ->where('owner_id', $ownerId->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (OwnerPushSubscriptionModel $m): OwnerPushSubscription => $this->mapper->toDomain($m))
            ->all();
    }

    public function deleteByEndpoint(PushSubscriptionEndpoint $endpoint): void
    {
        OwnerPushSubscriptionModel::query()
            ->where('endpoint', $endpoint->value)
            ->delete();
    }

    public function markGone(PushSubscriptionEndpoint $endpoint): void
    {
        $this->deleteByEndpoint($endpoint);
    }
}
