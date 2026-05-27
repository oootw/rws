<?php

declare(strict_types=1);

namespace App\Application\Places\GetPublicPlaceView;

use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesHandler;
use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesQuery;
use App\Domain\Iam\Feature;
use App\Domain\Places\Place;

/**
 * Маппит уже разрешённый агрегат Place в публичный read-model.
 * Принимает Place, а не id, потому что разрешение прав/активности
 * уже сделано middleware'ом через ResolvePublicPlace.
 *
 * Список scan-relevant фич owner'а подмешиваем сюда (для брендинга
 * страницы сканирования). Owner-only фичи (например `csv_export_reviews`)
 * на публичный endpoint не утекают.
 */
final readonly class GetPublicPlaceViewHandler
{
    /**
     * Whitelist: какие фичи имеют смысл на scan-стороне.
     * Расширяется вместе с появлением реальных scan-side-фич.
     */
    private const SCAN_FEATURES = [
        Feature::CustomBranding,
        Feature::QrThemes,
    ];

    public function __construct(
        private GetOwnerFeaturesHandler $features,
    ) {}

    public function handle(Place $place): PublicPlaceView
    {
        $ownerFeatures = $this->features->handle(
            new GetOwnerFeaturesQuery(ownerId: $place->ownerId->value),
        );

        $scanFeatures = array_values(array_map(
            static fn (Feature $f) => $f->value,
            array_filter(
                $ownerFeatures,
                static fn (Feature $f) => in_array($f, self::SCAN_FEATURES, true),
            ),
        ));

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
            tariffFeatures: $scanFeatures,
        );
    }
}
