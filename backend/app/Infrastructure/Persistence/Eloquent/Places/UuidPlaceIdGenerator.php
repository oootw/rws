<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Places;

use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceIdGenerator;
use Illuminate\Support\Str;

final class UuidPlaceIdGenerator implements PlaceIdGenerator
{
    public function next(): PlaceId
    {
        return new PlaceId((string) Str::uuid());
    }
}
