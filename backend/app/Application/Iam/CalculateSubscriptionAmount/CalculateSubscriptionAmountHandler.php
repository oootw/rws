<?php

declare(strict_types=1);

namespace App\Application\Iam\CalculateSubscriptionAmount;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffRepository;
use App\Domain\Places\PlaceRepository;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Use case: сколько владельцу платить за следующий период.
 *
 * Формула: первая точка по basePrice тарифа, каждая следующая — по extraPlacePrice.
 * Если у владельца тарифа нет (старые записи без миграции) — fallback на env config.
 *
 * Возвращаемое значение — копейки.
 */
final readonly class CalculateSubscriptionAmountHandler
{
    public function __construct(
        private OwnerRepository $owners,
        private TariffRepository $tariffs,
        private PlaceRepository $places,
        private Config $config,
    ) {}

    public function handle(CalculateSubscriptionAmountQuery $query): int
    {
        $ownerId = new OwnerId($query->ownerId);
        $tariff = $this->resolveTariff($ownerId);

        $basePrice = $tariff?->basePrice
            ?: (int) $this->config->get('guardreviews.subscription.base_price');
        $extraPlacePrice = $tariff?->extraPlacePrice
            ?: (int) $this->config->get('guardreviews.subscription.extra_place_price');

        $placesCount = $this->places->countByOwner($ownerId);

        if ($placesCount <= 1) {
            return $basePrice;
        }

        return $basePrice + ($placesCount - 1) * $extraPlacePrice;
    }

    private function resolveTariff(OwnerId $ownerId): ?Tariff
    {
        $owner = $this->owners->findById($ownerId);

        if ($owner === null || $owner->tariffId() === null) {
            return null;
        }

        return $this->tariffs->findById($owner->tariffId());
    }
}
