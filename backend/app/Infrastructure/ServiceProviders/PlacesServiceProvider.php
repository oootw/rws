<?php

declare(strict_types=1);

namespace App\Infrastructure\ServiceProviders;

use App\Domain\Places\PlaceIdGenerator;
use App\Domain\Places\PlaceRepository;
use App\Infrastructure\Persistence\Eloquent\Places\EloquentPlaceRepository;
use App\Infrastructure\Persistence\Eloquent\Places\UuidPlaceIdGenerator;
use Illuminate\Support\ServiceProvider;

final class PlacesServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        PlaceRepository::class => EloquentPlaceRepository::class,
        PlaceIdGenerator::class => UuidPlaceIdGenerator::class,
    ];
}
