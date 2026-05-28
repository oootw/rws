<?php

declare(strict_types=1);

namespace App\Application\Iam\ListPushSubscriptionsForOwner;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\PushSubscriptionRepository;
use App\Domain\Notifications\PushSubscriptionView;

/**
 * Reader: возвращает push-подписки владельца в виде,
 * пригодном для каналов доставки (OwnerContact / WebPushNotificationChannel).
 * Iam-сущность OwnerPushSubscription не утекает наружу.
 */
final readonly class ListPushSubscriptionsForOwnerHandler
{
    public function __construct(
        private PushSubscriptionRepository $subscriptions,
    ) {}

    /**
     * @return list<PushSubscriptionView>
     */
    public function handle(ListPushSubscriptionsForOwnerQuery $query): array
    {
        return array_map(
            static fn (OwnerPushSubscription $s): PushSubscriptionView => new PushSubscriptionView(
                endpoint: $s->endpoint->value,
                p256dh: $s->p256dh,
                auth: $s->auth,
            ),
            $this->subscriptions->listByOwner(new OwnerId($query->ownerId)),
        );
    }
}
