<?php

declare(strict_types=1);

namespace App\Domain\Reviews\Events;

use App\Domain\Places\PlaceId;
use App\Domain\Reviews\ReviewId;
use App\Domain\Shared\Events\DomainEvent;
use DateTimeImmutable;

final readonly class NegativeReviewSubmitted implements DomainEvent
{
    public function __construct(
        public ReviewId $reviewId,
        public PlaceId $placeId,
        public DateTimeImmutable $submittedAt,
    ) {}
}
