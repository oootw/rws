<?php

declare(strict_types=1);

namespace App\Domain\Places;

interface PlaceIdGenerator
{
    public function next(): PlaceId;
}
