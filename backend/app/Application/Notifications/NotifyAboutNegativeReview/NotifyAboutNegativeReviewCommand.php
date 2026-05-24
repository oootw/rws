<?php

declare(strict_types=1);

namespace App\Application\Notifications\NotifyAboutNegativeReview;

use App\Domain\Notifications\OwnerContact;

final readonly class NotifyAboutNegativeReviewCommand
{
    public function __construct(
        public OwnerContact $contact,
        public string $reviewId,
        public string $placeTitle,
        public int $stars,
        public string $reviewText,
        public string $reviewerContact,
    ) {}
}
