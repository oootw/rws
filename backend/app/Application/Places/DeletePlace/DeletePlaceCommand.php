<?php

declare(strict_types=1);

namespace App\Application\Places\DeletePlace;

final readonly class DeletePlaceCommand
{
    public function __construct(
        public string $placeId,
    ) {}
}
