<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Reviews;

use App\Domain\Reviews\Review;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewRepository;
use App\Models\Review as ReviewModel;

final readonly class EloquentReviewRepository implements ReviewRepository
{
    public function __construct(
        private ReviewMapper $mapper,
    ) {}

    public function save(Review $review): void
    {
        $model = ReviewModel::query()->find($review->id->value) ?? new ReviewModel;

        $this->mapper->toPersistence($review, $model)->save();
    }

    public function findById(ReviewId $id): ?Review
    {
        $model = ReviewModel::query()->find($id->value);

        return $model === null ? null : $this->mapper->toDomain($model);
    }
}
