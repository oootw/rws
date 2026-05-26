<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Reviews;

use App\Application\Reviews\ListOwnerReviews\OwnerReviewFilters;
use App\Application\Reviews\ListOwnerReviews\OwnerReviewsPage;
use App\Application\Reviews\ListOwnerReviews\OwnerReviewsReader;
use App\Application\Reviews\ListOwnerReviews\OwnerReviewView;
use App\Domain\Iam\OwnerId;
use App\Enums\ReviewStatus;
use App\Models\Review as ReviewModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentOwnerReviewsReader implements OwnerReviewsReader
{
    public function paginate(
        OwnerId $ownerId,
        OwnerReviewFilters $filters,
        int $page,
        int $perPage,
    ): OwnerReviewsPage {
        $query = $this->baseQuery($ownerId, $filters);

        $total = (clone $query)->count();

        $items = $query
            ->with('place:id,title')
            ->latest('reviews.created_at')
            ->forPage($page, $perPage)
            ->get()
            ->map(self::toView(...))
            ->values()
            ->all();

        return new OwnerReviewsPage(
            items: $items,
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @return Builder<ReviewModel>
     */
    private function baseQuery(OwnerId $ownerId, OwnerReviewFilters $filters): Builder
    {
        $query = ReviewModel::query()
            ->whereHas('place', static fn (Builder $q) => $q->where('user_id', $ownerId->value));

        if ($filters->status !== null) {
            $query->where('status', $filters->status->value);
        }

        if ($filters->placeId !== null) {
            $query->where('place_id', $filters->placeId);
        }

        if ($filters->from !== null) {
            $query->where('reviews.created_at', '>=', $filters->from);
        }

        if ($filters->until !== null) {
            $query->where('reviews.created_at', '<=', $filters->until);
        }

        return $query;
    }

    private static function toView(ReviewModel $review): OwnerReviewView
    {
        $status = $review->status instanceof ReviewStatus
            ? $review->status
            : ReviewStatus::from((string) $review->status);

        return new OwnerReviewView(
            id: (string) $review->id,
            placeId: (string) $review->place_id,
            placeTitle: (string) ($review->place?->title ?? ''),
            stars: (int) $review->stars,
            status: $status,
            contact: (string) $review->contact,
            text: (string) $review->text,
            createdAt: DateTimeImmutable::createFromInterface($review->created_at),
        );
    }
}
