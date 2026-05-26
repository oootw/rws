<?php

declare(strict_types=1);

namespace App\Application\Reviews\ListOwnerReviews;

use App\Enums\ReviewStatus;
use DateTimeImmutable;

final readonly class OwnerReviewView
{
    public function __construct(
        public string $id,
        public string $placeId,
        public string $placeTitle,
        public int $stars,
        public ReviewStatus $status,
        public string $contact,
        public string $text,
        public DateTimeImmutable $createdAt,
    ) {}
}
