<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListRecentReviewsForOwner;

interface RecentReviewsReader
{
    /**
     * @return list<RecentReviewView>
     */
    public function recentForOwner(string $ownerId, int $limit): array;
}
