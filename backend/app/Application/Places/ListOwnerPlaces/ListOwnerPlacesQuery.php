<?php

declare(strict_types=1);

namespace App\Application\Places\ListOwnerPlaces;

final readonly class ListOwnerPlacesQuery
{
    public function __construct(public string $ownerId) {}
}
