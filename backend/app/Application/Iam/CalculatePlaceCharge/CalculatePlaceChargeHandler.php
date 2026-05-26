<?php

declare(strict_types=1);

namespace App\Application\Iam\CalculatePlaceCharge;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffRepository;
use App\Domain\Shared\Clock\Clock;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Use case: посчитать pro-rata доплату за добавление следующей точки в
 * текущий оплаченный период.
 *
 * Правила:
 *  - подписка не активна → доплата 0 (точка добавляется бесплатно, оплата
 *    войдёт в первое продление);
 *  - подписка активна → доплата = round(extraPlacePrice × daysLeft / durationDays).
 *
 * Возврат — PlaceCharge: и сумма, и контекст для UI («со след. месяца +X»).
 */
final readonly class CalculatePlaceChargeHandler
{
    private const DEFAULT_PERIOD_DAYS = 30;

    public function __construct(
        private OwnerRepository $owners,
        private TariffRepository $tariffs,
        private Clock $clock,
        private Config $config,
    ) {}

    public function handle(CalculatePlaceChargeQuery $query): PlaceCharge
    {
        $owner = $this->owners->findById(new OwnerId($query->ownerId));

        if ($owner === null) {
            throw new TenantNotFound;
        }

        $now = $this->clock->now();
        $extraPlacePrice = $this->resolveExtraPlacePrice($owner->tariffId() === null ? null : $this->tariffs->findById($owner->tariffId()));
        $periodDays = (int) $this->config->get('guardreviews.subscription.duration_days', self::DEFAULT_PERIOD_DAYS);

        if (! $owner->hasActiveSubscriptionAt($now)) {
            return new PlaceCharge(
                prorataAmount: 0,
                daysLeft: 0,
                monthlyDelta: $extraPlacePrice,
                requiresPayment: false,
            );
        }

        $endsAt = $owner->subscription()->endsAt;
        $diffSeconds = max(0, $endsAt->getTimestamp() - $now->getTimestamp());
        $daysLeft = (int) ceil($diffSeconds / 86_400);

        $prorata = $periodDays > 0
            ? (int) round($extraPlacePrice * $daysLeft / $periodDays)
            : 0;

        return new PlaceCharge(
            prorataAmount: $prorata,
            daysLeft: $daysLeft,
            monthlyDelta: $extraPlacePrice,
            requiresPayment: $prorata > 0,
        );
    }

    private function resolveExtraPlacePrice(?Tariff $tariff): int
    {
        return $tariff?->extraPlacePrice
            ?: (int) $this->config->get('guardreviews.subscription.extra_place_price');
    }
}
