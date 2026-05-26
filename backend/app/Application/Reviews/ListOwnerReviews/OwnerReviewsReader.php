<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListOwnerReviews;

use App\Domain\Iam\OwnerId;

interface OwnerReviewsReader
{
    public function paginate(
        OwnerId $ownerId,
        OwnerReviewFilters $filters,
        int $page,
        int $perPage,
    ): OwnerReviewsPage;
}
