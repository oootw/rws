<?php

declare(strict_types=1);

namespace App\Application\Places\ResolvePublicPlace;

final readonly class ResolvePublicPlaceQuery
{
    public function __construct(
        public string $placeId,
        public string $ownerId,
    ) {}
}
