<?php

declare(strict_types=1);

namespace App\Application\Places\GetPlaceForOwner;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;

/**
 * Возвращает точку, только если она принадлежит данному владельцу.
 * Иначе — null (никаких 403/404 в use case'е; интерфейс решает, что
 * это означает).
 */
final readonly class GetPlaceForOwnerHandler
{
    public function __construct(
        private PlaceRepository $places,
    ) {}

    public function handle(GetPlaceForOwnerQuery $query): ?Place
    {
        $place = $this->places->findById(new PlaceId($query->placeId));

        if ($place === null || ! $place->isOwnedBy(new OwnerId($query->ownerId))) {
            return null;
        }

        return $place;
    }
}
