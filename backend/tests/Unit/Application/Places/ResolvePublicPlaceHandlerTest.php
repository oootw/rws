<?php

declare(strict_types=1);

use App\Application\Places\Exceptions\PlaceUnavailable;
use App\Application\Places\ResolvePublicPlace\ResolvePublicPlaceHandler;
use App\Application\Places\ResolvePublicPlace\ResolvePublicPlaceQuery;
use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;
use App\Domain\Places\Title;

function fakePlacesRepo(?Place $stored = null): PlaceRepository
{
    return new class($stored) implements PlaceRepository
    {
        public function __construct(public ?Place $stored) {}

        public function save(Place $place): void
        {
            $this->stored = $place;
        }

        public function findById(PlaceId $id): ?Place
        {
            if ($this->stored === null) {
                return null;
            }

            return $this->stored->id->equals($id) ? $this->stored : null;
        }

        public function findAllByOwner(OwnerId $ownerId): array
        {
            return $this->stored !== null && $this->stored->isOwnedBy($ownerId) ? [$this->stored] : [];
        }

        public function countByOwner(OwnerId $ownerId): int
        {
            return count($this->findAllByOwner($ownerId));
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

it('возвращает активную точку нужного владельца', function (): void {
    $handler = new ResolvePublicPlaceHandler(fakePlacesRepo(activePlace()));

    $place = $handler->handle(new ResolvePublicPlaceQuery(
        placeId: '11111111-1111-1111-1111-111111111111',
        ownerId: '22222222-2222-2222-2222-222222222222',
    ));

    expect($place->id->value)->toBe('11111111-1111-1111-1111-111111111111');
});

it('бросает PlaceUnavailable, если точки нет', function (): void {
    (new ResolvePublicPlaceHandler(fakePlacesRepo()))->handle(
        new ResolvePublicPlaceQuery(placeId: 'x', ownerId: 'y'),
    );
})->throws(PlaceUnavailable::class);

it('бросает PlaceUnavailable для неактивной точки', function (): void {
    (new ResolvePublicPlaceHandler(fakePlacesRepo(inactivePlace())))->handle(
        new ResolvePublicPlaceQuery(
            placeId: '11111111-1111-1111-1111-111111111111',
            ownerId: '22222222-2222-2222-2222-222222222222',
        ),
    );
})->throws(PlaceUnavailable::class);

it('бросает PlaceUnavailable для чужой точки', function (): void {
    (new ResolvePublicPlaceHandler(fakePlacesRepo(activePlace())))->handle(
        new ResolvePublicPlaceQuery(
            placeId: '11111111-1111-1111-1111-111111111111',
            ownerId: '99999999-9999-9999-9999-999999999999',
        ),
    );
})->throws(PlaceUnavailable::class);
