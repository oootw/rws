<?php

declare(strict_types=1);

namespace App\Domain\Places;

use App\Domain\Iam\OwnerId;

interface PlaceRepository
{
    public function save(Place $place): void;

    public function findById(PlaceId $id): ?Place;

    /**
     * @return list<Place>
     */
    public function findAllByOwner(OwnerId $ownerId): array;

    public function countByOwner(OwnerId $ownerId): int;
}
