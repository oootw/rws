<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListRecentReviewsForOwner;

final readonly class ListRecentReviewsForOwnerQuery
{
    public function __construct(
        public string $ownerId,
        public int $limit = 20,
    ) {}
}
