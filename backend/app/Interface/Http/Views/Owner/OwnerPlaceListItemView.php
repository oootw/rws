<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Application\Places\ListOwnerPlaces\OwnerPlaceSummary;

final readonly class OwnerPlaceListItemView
{
    /**
     * @return array{
     *     id: string,
     *     title: string,
     *     platforms_count: int,
     *     is_active: bool,
     * }
     */
    public static function fromSummary(OwnerPlaceSummary $summary): array
    {
        return [
            'id' => $summary->id,
            'title' => $summary->title,
            'platforms_count' => $summary->platformCount,
            'is_active' => $summary->isActive,
        ];
    }
}
