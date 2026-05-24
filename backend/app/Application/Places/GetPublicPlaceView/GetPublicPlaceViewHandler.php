<?php

declare(strict_types=1);

namespace App\Application\Places\GetPublicPlaceView;

use App\Domain\Places\Place;

/**
 * Маппит уже разрешённый агрегат Place в публичный read-model.
 * Принимает Place, а не id, потому что разрешение прав/активности
 * уже сделано middleware'ом через ResolvePublicPlace.
 */
final class GetPublicPlaceViewHandler
{
    public function handle(Place $place): PublicPlaceView
    {
        return new PublicPlaceView(
            id: $place->id->value,
            title: $place->title()->value,
            backgroundImageUrl: $place->backgroundImageUrl(),
            platforms: array_map(
                static fn ($platform): array => [
                    'type' => $platform->type->value,
                    'url' => $platform->url,
                    'label' => $platform->label,
                ],
                $place->platforms(),
            ),
        );
    }
}
