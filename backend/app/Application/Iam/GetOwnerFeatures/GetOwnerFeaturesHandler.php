<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerFeatures;

use App\Domain\Iam\Feature;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffRepository;

/**
 * Возвращает список фич, доступных владельцу.
 *
 * Резолюция тарифа повторяет логику {@see GetOwnerSubscriptionHandler::resolveTariff()}:
 * сначала привязанный к owner'у тариф, при его отсутствии — default.
 * Если default тоже отсутствует — `[]` (всё закрыто).
 *
 * Это **единственный** легитимный способ узнать «какие фичи у owner'а»:
 * `RequireFeature` middleware и `GET /api/owner/features` оба используют этот handler.
 */
final readonly class GetOwnerFeaturesHandler
{
    public function __construct(
        private OwnerRepository $owners,
        private TariffRepository $tariffs,
    ) {}

    /**
     * @return list<Feature>
     */
    public function handle(GetOwnerFeaturesQuery $query): array
    {
        $owner = $this->owners->findById(new OwnerId($query->ownerId));

        if ($owner === null) {
            return [];
        }

        $tariff = $this->resolveTariff($owner);

        return $tariff?->features ?? [];
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
