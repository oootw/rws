<?php

declare(strict_types=1);

namespace App\Application\Reviews\ChangeReviewStatus;

use App\Enums\ReviewStatus;

final readonly class ChangeReviewStatusCommand
{
    public function __construct(
        public string $reviewId,
        public string $ownerId,
        public ReviewStatus $newStatus,
    ) {}
}
