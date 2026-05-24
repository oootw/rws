<?php

declare(strict_types=1);

namespace App\Application\Places\ResolvePublicPlace;

use App\Application\Places\Exceptions\PlaceUnavailable;
use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;

/**
 * Разрешает публичную точку для конкретного тенанта.
 * Кидает PlaceUnavailable, если:
 *   - точки нет,
 *   - она не принадлежит тенанту,
 *   - она выключена (is_active = false).
 *
 * Не различает эти случаи наружу намеренно: 404 для всех.
 */
final readonly class ResolvePublicPlaceHandler
{
    public function __construct(
        private PlaceRepository $places,
    ) {}

    public function handle(ResolvePublicPlaceQuery $query): Place
    {
        $place = $this->places->findById(new PlaceId($query->placeId));

        if ($place === null || ! $place->isActive() || ! $place->isOwnedBy(new OwnerId($query->ownerId))) {
            throw new PlaceUnavailable;
        }

        return $place;
    }
}
