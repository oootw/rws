<?php

declare(strict_types=1);

namespace App\Application\Iam\UnregisterPushSubscription;

use App\Application\Iam\Exceptions\PushSubscriptionNotFound;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\PushSubscriptionRepository;

/**
 * Use case: SPA отзывает push-подписку текущего устройства.
 *
 * Чужой endpoint (другого owner-а) трактуем как отсутствующий —
 * не позволяем «угонять» подписку через DELETE.
 */
final readonly class UnregisterPushSubscriptionHandler
{
    public function __construct(
        private PushSubscriptionRepository $subscriptions,
    ) {}

    public function handle(UnregisterPushSubscriptionCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $endpoint = new PushSubscriptionEndpoint($command->endpoint);

        $existing = $this->subscriptions->findByEndpoint($endpoint);

        if ($existing === null || ! $existing->ownerId->equals($ownerId)) {
            throw new PushSubscriptionNotFound;
        }

        $this->subscriptions->deleteByEndpoint($endpoint);
    }
}
