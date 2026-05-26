<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListOwnerReviews;

use App\Enums\ReviewStatus;
use DateTimeImmutable;

final readonly class OwnerReviewFilters
{
    public function __construct(
        public ?ReviewStatus $status = null,
        public ?string $placeId = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $until = null,
    ) {}
}
