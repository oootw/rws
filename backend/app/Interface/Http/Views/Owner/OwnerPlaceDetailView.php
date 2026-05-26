<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Domain\Iam\Owner;
use App\Domain\Places\Place;
use App\Domain\Places\PlatformLink;
use App\Services\QrCodeService;

final readonly class OwnerPlaceDetailView
{
    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     is_active: bool,
     *     background_image_url: ?string,
     *     scan_url: string,
     *     qr_png_url: string,
     *     platforms: list<array{type: string, url: string, label: string}>,
     * }
     */
    public static function build(Place $place, Owner $owner, QrCodeService $qrCodeService, string $appDomain): array
    {
        $scanBase = $owner->scanBaseUrl($appDomain);
        $scanUrl = $qrCodeService->placeScanUrl($scanBase, $place);

        return [
            'id' => $place->id->value,
            'title' => $place->title()->value,
            'is_active' => $place->isActive(),
            'background_image_url' => $place->backgroundImageUrl(),
            'scan_url' => $scanUrl,
            'qr_png_url' => '/api/owner/places/'.$place->id->value.'/qr.png',
            'platforms' => array_map(self::platformPayload(...), $place->platforms()),
        ];
    }

    /** @return array{type: string, url: string, label: string} */
    private static function platformPayload(PlatformLink $platform): array
    {
        return [
            'type' => $platform->type->value,
            'url' => $platform->url,
            'label' => $platform->label,
        ];
    }
}
