<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListOwnerReviews;

use App\Domain\Iam\OwnerId;

final readonly class ListOwnerReviewsHandler
{
    public function __construct(
        private OwnerReviewsReader $reader,
    ) {}

    public function handle(ListOwnerReviewsQuery $query): OwnerReviewsPage
    {
        return $this->reader->paginate(
            ownerId: new OwnerId($query->ownerId),
            filters: $query->filters,
            page: max(1, $query->page),
            perPage: max(1, min(100, $query->perPage)),
        );
    }
}
