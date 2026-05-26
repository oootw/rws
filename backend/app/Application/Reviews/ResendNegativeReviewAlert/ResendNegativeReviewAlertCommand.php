<?php

declare(strict_types=1);

namespace App\Application\Reviews\ResendNegativeReviewAlert;

final readonly class ResendNegativeReviewAlertCommand
{
    public function __construct(
        public string $reviewId,
    ) {}
}
