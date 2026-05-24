<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Places;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlaceRepository;
use App\Models\Place as PlaceModel;

final readonly class EloquentPlaceRepository implements PlaceRepository
{
    public function __construct(
        private PlaceMapper $mapper,
    ) {}

    public function save(Place $place): void
    {
        $model = PlaceModel::query()->find($place->id->value) ?? new PlaceModel;

        $this->mapper->toPersistence($place, $model)->save();
    }

    public function findById(PlaceId $id): ?Place
    {
        $model = PlaceModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    /**
     * @return list<Place>
     */
    public function findAllByOwner(OwnerId $ownerId): array
    {
        return PlaceModel::query()
            ->where('user_id', $ownerId->value)
            ->latest()
            ->get()
            ->map(fn (PlaceModel $model): Place => $this->mapper->toDomain($model))
            ->values()
            ->all();
    }

    public function countByOwner(OwnerId $ownerId): int
    {
        return PlaceModel::query()->where('user_id', $ownerId->value)->count();
    }
}
