<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceIdGenerator;
use App\Domain\Places\PlaceRepository;
use App\Domain\Places\Title;

function fakePlaceIdGenerator(string $value): PlaceIdGenerator
{
    return new class($value) implements PlaceIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): PlaceId
        {
            return new PlaceId($this->value);
        }
    };
}

/**
 * @param  list<Place>  $places
 */
function fakePlacesRepository(array $places = []): PlaceRepository
{
    return new class($places) implements PlaceRepository
    {
        /** @var list<Place> */
        public array $places;

        /** @param  list<Place>  $places */
        public function __construct(array $places)
        {
            $this->places = $places;
        }

        public function save(Place $place): void
        {
            foreach ($this->places as $index => $stored) {
                if ($stored->id->equals($place->id)) {
                    $this->places[$index] = $place;

                    return;
                }
            }

            $this->places[] = $place;
        }

        public function findById(PlaceId $id): ?Place
        {
            foreach ($this->places as $place) {
                if ($place->id->equals($id)) {
                    return $place;
                }
            }

            return null;
        }

        public function findAllByOwner(OwnerId $ownerId): array
        {
            return array_values(array_filter(
                $this->places,
                fn (Place $place): bool => $place->isOwnedBy($ownerId),
            ));
        }

        public function countByOwner(OwnerId $ownerId): int
        {
            return count($this->findAllByOwner($ownerId));
        }

        public function delete(PlaceId $id): void
        {
            $this->places = array_values(array_filter(
                $this->places,
                static fn (Place $place): bool => ! $place->id->equals($id),
            ));
        }
    };
}

function activePlace(string $ownerId = '22222222-2222-2222-2222-222222222222'): Place
{
    return Place::register(
        id: new PlaceId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId($ownerId),
        title: new Title('Кафе Уют'),
        platforms: [],
        backgroundImageUrl: null,
    );
}

function inactivePlace(): Place
{
    return Place::restore(
        id: new PlaceId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        title: new Title('Кафе Уют'),
        platforms: [],
        backgroundImageUrl: null,
        isActive: false,
    );
}
