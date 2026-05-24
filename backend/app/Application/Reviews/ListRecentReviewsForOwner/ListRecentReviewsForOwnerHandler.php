<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListRecentReviewsForOwner;

final readonly class ListRecentReviewsForOwnerHandler
{
    public function __construct(
        private RecentReviewsReader $reader,
    ) {}

    /**
     * @return list<RecentReviewView>
     */
    public function handle(ListRecentReviewsForOwnerQuery $query): array
    {
        return $this->reader->recentForOwner($query->ownerId, $query->limit);
    }
}
