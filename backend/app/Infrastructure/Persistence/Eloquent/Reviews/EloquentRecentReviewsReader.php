<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Reviews;

use App\Application\Reviews\ListRecentReviewsForOwner\RecentReviewsReader;
use App\Application\Reviews\ListRecentReviewsForOwner\RecentReviewView;
use App\Enums\ReviewStatus;
use App\Models\Review as ReviewModel;

final class EloquentRecentReviewsReader implements RecentReviewsReader
{
    public function recentForOwner(string $ownerId, int $limit): array
    {
        return ReviewModel::query()
            ->whereHas('place', fn ($query) => $query->where('user_id', $ownerId))
            ->latest()
            ->limit($limit)
            ->with('place:id,title')
            ->get()
            ->map(fn (ReviewModel $review) => new RecentReviewView(
                id: (string) $review->id,
                placeTitle: (string) $review->place?->title,
                stars: (int) $review->stars,
                status: $review->status instanceof ReviewStatus
                    ? $review->status
                    : ReviewStatus::from((string) $review->status),
                contact: (string) $review->contact,
                text: (string) $review->text,
            ))
            ->values()
            ->all();
    }
}
