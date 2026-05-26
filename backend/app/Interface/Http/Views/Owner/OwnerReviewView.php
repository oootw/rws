<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Application\Reviews\ListOwnerReviews\OwnerReviewView as ReviewProjection;

final readonly class OwnerReviewView
{
    /**
     * @return array{
     *     id: string,
     *     place_id: string,
     *     place_title: string,
     *     stars: int,
     *     status: string,
     *     contact: string,
     *     text: string,
     *     created_at: string,
     * }
     */
    public static function fromProjection(ReviewProjection $review): array
    {
        return [
            'id' => $review->id,
            'place_id' => $review->placeId,
            'place_title' => $review->placeTitle,
            'stars' => $review->stars,
            'status' => $review->status->value,
            'contact' => $review->contact,
            'text' => $review->text,
            'created_at' => $review->createdAt->format(DATE_ATOM),
        ];
    }
}
