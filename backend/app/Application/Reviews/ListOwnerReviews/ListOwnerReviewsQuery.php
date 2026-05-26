<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListOwnerReviews;

final readonly class ListOwnerReviewsQuery
{
    public function __construct(
        public string $ownerId,
        public OwnerReviewFilters $filters,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
