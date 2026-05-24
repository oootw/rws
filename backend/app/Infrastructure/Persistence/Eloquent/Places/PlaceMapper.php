<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Places;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Domain\Places\PlaceId;
use App\Domain\Places\PlatformLink;
use App\Domain\Places\PlatformType;
use App\Domain\Places\Title;
use App\Models\Place as PlaceModel;

/**
 * Переводит Eloquent\Place <-> доменный Place.
 * Знание формата хранения платформ (массив строк в JSON-колонке)
 * заперто в этом классе.
 */
final class PlaceMapper
{
    public function toDomain(PlaceModel $model): Place
    {
        return Place::restore(
            id: new PlaceId((string) $model->id),
            ownerId: new OwnerId((string) $model->user_id),
            title: new Title((string) $model->title),
            platforms: $this->platformsFromRaw($model->platforms ?? []),
            backgroundImageUrl: $model->background_image_url,
            isActive: (bool) $model->is_active,
        );
    }

    public function toPersistence(Place $place, PlaceModel $model): PlaceModel
    {
        $model->id = $place->id->value;
        $model->user_id = $place->ownerId->value;
        $model->title = $place->title()->value;
        $model->platforms = $this->platformsToRaw($place->platforms());
        $model->background_image_url = $place->backgroundImageUrl();
        $model->is_active = $place->isActive();

        return $model;
    }

    /**
     * @param  array<int, array<string, mixed>>  $raw
     * @return list<PlatformLink>
     */
    private function platformsFromRaw(array $raw): array
    {
        $platforms = [];

        foreach ($raw as $entry) {
            $url = isset($entry['url']) ? (string) $entry['url'] : '';

            if ($url === '') {
                continue;
            }

            $platforms[] = new PlatformLink(
                type: PlatformType::from((string) $entry['type']),
                url: $url,
                label: (string) $entry['label'],
            );
        }

        return $platforms;
    }

    /**
     * @param  list<PlatformLink>  $platforms
     * @return list<array{type: string, url: string, label: string}>
     */
    private function platformsToRaw(array $platforms): array
    {
        return array_map(
            static fn (PlatformLink $platform): array => [
                'type' => $platform->type->value,
                'url' => $platform->url,
                'label' => $platform->label,
            ],
            $platforms,
        );
    }
}
