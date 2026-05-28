<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface PushSubscriptionRepository
{
    public function save(OwnerPushSubscription $subscription): void;

    public function findByEndpoint(PushSubscriptionEndpoint $endpoint): ?OwnerPushSubscription;

    /**
     * @return list<OwnerPushSubscription>
     */
    public function listByOwner(OwnerId $ownerId): array;

    public function deleteByEndpoint(PushSubscriptionEndpoint $endpoint): void;

    /**
     * Подписка стала «мёртвой» (push-сервис вернул 404/410).
     * Реализация удаляет запись — gone-подписки не подлежат восстановлению.
     */
    public function markGone(PushSubscriptionEndpoint $endpoint): void;
}
