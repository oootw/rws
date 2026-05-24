<?php

declare(strict_types=1);

namespace App\Application\Places\ListOwnerPlaces;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceRepository;

final readonly class ListOwnerPlacesHandler
{
    public function __construct(
        private PlaceRepository $places,
    ) {}

    /**
     * @return list<OwnerPlaceSummary>
     */
    public function handle(ListOwnerPlacesQuery $query): array
    {
        $places = $this->places->findAllByOwner(new OwnerId($query->ownerId));

        return array_map($this->summarize(...), $places);
    }

    private function summarize(Place $place): OwnerPlaceSummary
    {
        return new OwnerPlaceSummary(
            id: $place->id->value,
            title: $place->title()->value,
            platformCount: count($place->platforms()),
            isActive: $place->isActive(),
        );
    }
}
