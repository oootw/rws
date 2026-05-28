<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionId;
use App\Domain\Iam\OwnerPushSubscriptionIdGenerator;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\PushSubscriptionRepository;

/**
 * @param  list<OwnerPushSubscription>  $initial
 */
function fakePushSubscriptionRepository(array $initial = []): PushSubscriptionRepository
{
    return new class($initial) implements PushSubscriptionRepository
    {
        /** @var list<OwnerPushSubscription> */
        public array $subscriptions;

        /** @param  list<OwnerPushSubscription>  $initial */
        public function __construct(array $initial)
        {
            $this->subscriptions = $initial;
        }

        public function save(OwnerPushSubscription $subscription): void
        {
            foreach ($this->subscriptions as $index => $stored) {
                if ($stored->id->equals($subscription->id)) {
                    $this->subscriptions[$index] = $subscription;

                    return;
                }
            }

            $this->subscriptions[] = $subscription;
        }

        public function findByEndpoint(PushSubscriptionEndpoint $endpoint): ?OwnerPushSubscription
        {
            foreach ($this->subscriptions as $stored) {
                if ($stored->endpoint->equals($endpoint)) {
                    return $stored;
                }
            }

            return null;
        }

        public function listByOwner(OwnerId $ownerId): array
        {
            return array_values(array_filter(
                $this->subscriptions,
                static fn (OwnerPushSubscription $s) => $s->ownerId->equals($ownerId),
            ));
        }

        public function deleteByEndpoint(PushSubscriptionEndpoint $endpoint): void
        {
            $this->subscriptions = array_values(array_filter(
                $this->subscriptions,
                static fn (OwnerPushSubscription $s) => ! $s->endpoint->equals($endpoint),
            ));
        }

        public function markGone(PushSubscriptionEndpoint $endpoint): void
        {
            $this->deleteByEndpoint($endpoint);
        }
    };
}

/**
 * @param  list<string>  $ids
 */
function fakePushSubscriptionIdGenerator(array $ids): OwnerPushSubscriptionIdGenerator
{
    return new class($ids) implements OwnerPushSubscriptionIdGenerator
    {
        /** @var list<string> */
        private array $ids;

        /** @param  list<string>  $ids */
        public function __construct(array $ids)
        {
            $this->ids = $ids;
        }

        public function next(): OwnerPushSubscriptionId
        {
            $value = array_shift($this->ids) ?? throw new RuntimeException('No more fake ids');

            return new OwnerPushSubscriptionId($value);
        }
    };
}
