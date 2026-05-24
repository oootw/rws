<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

interface ReviewRepository
{
    public function save(Review $review): void;

    public function findById(ReviewId $id): ?Review;
}
