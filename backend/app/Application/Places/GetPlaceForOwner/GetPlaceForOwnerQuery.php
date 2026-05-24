<?php

declare(strict_types=1);

namespace App\Application\Places\GetPlaceForOwner;

final readonly class GetPlaceForOwnerQuery
{
    public function __construct(
        public string $placeId,
        public string $ownerId,
    ) {}
}
