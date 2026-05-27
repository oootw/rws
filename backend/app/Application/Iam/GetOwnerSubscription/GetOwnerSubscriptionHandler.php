<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerSubscription;

use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountHandler;
use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountQuery;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffRepository;
use App\Domain\Places\PlaceRepository;
use App\Domain\Shared\Clock\Clock;

/**
 * Сводка подписки владельца для Owner-панели: тариф, срок, активна ли,
 * сколько точек использовано против лимита тарифа, и сумма следующего платежа.
 */
final readonly class GetOwnerSubscriptionHandler
{
    private const SECONDS_PER_DAY = 86_400;

    public function __construct(
        private OwnerRepository $owners,
        private TariffRepository $tariffs,
        private PlaceRepository $places,
        private CalculateSubscriptionAmountHandler $calculateAmount,
        private Clock $clock,
    ) {}

    public function handle(GetOwnerSubscriptionQuery $query): ?OwnerSubscriptionView
    {
        $owner = $this->owners->findById(new OwnerId($query->ownerId));

        if ($owner === null) {
            return null;
        }

        $tariff = $this->resolveTariff($owner);
        $now = $this->clock->now();
        $subscription = $owner->subscription();
        $endsAt = $subscription->endsAt;

        $daysLeft = 0;
        if ($endsAt !== null && $endsAt > $now) {
            $daysLeft = (int) ceil(($endsAt->getTimestamp() - $now->getTimestamp()) / self::SECONDS_PER_DAY);
        }

        $nextChargeAmount = $this->calculateAmount->handle(
            new CalculateSubscriptionAmountQuery(ownerId: $owner->id->value),
        );

        return new OwnerSubscriptionView(
            tariffId: $tariff?->id->value,
            tariffTitle: $tariff?->title,
            endsAt: $endsAt,
            daysLeft: $daysLeft,
            isActive: $subscription->isActiveAt($now),
            placesUsed: $this->places->countByOwner($owner->id),
            placesLimit: $tariff?->placesLimit,
            nextChargeAmount: $nextChargeAmount,
        );
    }

    private function resolveTariff(Owner $owner): ?Tariff
    {
        $tariffId = $owner->tariffId();

        if ($tariffId !== null) {
            $tariff = $this->tariffs->findById($tariffId);
            if ($tariff !== null) {
                return $tariff;
            }
        }

        return $this->tariffs->findDefault();
    }
}
