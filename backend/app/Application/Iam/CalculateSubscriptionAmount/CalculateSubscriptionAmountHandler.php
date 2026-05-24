<?php

declare(strict_types=1);

namespace App\Application\Iam\CalculateSubscriptionAmount;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\PlaceRepository;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Use case: сколько владельцу платить за следующий период.
 *
 * Формула: первая точка по базовой цене, каждая следующая — по доплате.
 * Pricing-параметры — деталь конфига; счёт точек идёт через PlaceRepository
 * (Places-контекст), к owner это не относится напрямую.
 *
 * Возвращаемое значение — целое число копеек (как и хранится в БД и
 * как отправляется в Тинькофф).
 */
final readonly class CalculateSubscriptionAmountHandler
{
    public function __construct(
        private PlaceRepository $places,
        private Config $config,
    ) {}

    public function handle(CalculateSubscriptionAmountQuery $query): int
    {
        $placesCount = $this->places->countByOwner(new OwnerId($query->ownerId));

        $basePrice = (int) $this->config->get('guardreviews.subscription.base_price');
        $extraPlacePrice = (int) $this->config->get('guardreviews.subscription.extra_place_price');

        if ($placesCount <= 1) {
            return $basePrice;
        }

        return $basePrice + ($placesCount - 1) * $extraPlacePrice;
    }
}
