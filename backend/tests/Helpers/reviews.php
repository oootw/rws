<?php

declare(strict_types=1);

use App\Domain\Places\PlaceId;
use App\Domain\Reviews\ContactInfo;
use App\Domain\Reviews\Review;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewRepository;
use App\Domain\Reviews\ReviewText;
use App\Domain\Reviews\Stars;
use App\Enums\ReviewStatus;

function sampleReview(
    string $reviewId = '11111111-1111-1111-1111-111111111111',
    string $placeId = '22222222-2222-2222-2222-222222222222',
): Review {
    return Review::submit(
        id: new ReviewId($reviewId),
        placeId: new PlaceId($placeId),
        stars: new Stars(2),
        text: new ReviewText('Долго ждали'),
        contact: new ContactInfo('test@example.com'),
        ipHash: null,
        submittedAt: new DateTimeImmutable('2026-05-24T12:00:00Z'),
    );
}

function restoredReview(
    ReviewStatus $status = ReviewStatus::New,
    string $reviewId = '11111111-1111-1111-1111-111111111111',
    string $placeId = '22222222-2222-2222-2222-222222222222',
): Review {
    return Review::restore(
        id: new ReviewId($reviewId),
        placeId: new PlaceId($placeId),
        stars: new Stars(2),
        text: new ReviewText('Долго ждали'),
        contact: new ContactInfo('test@example.com'),
        ipHash: null,
        submittedAt: new DateTimeImmutable('2026-05-24T12:00:00Z'),
        status: $status,
    );
}

/**
 * @param  list<Review>  $reviews
 */
function fakeReviewsRepository(array $reviews = []): ReviewRepository
{
    return new class($reviews) implements ReviewRepository
    {
        /** @var list<Review> */
        public array $reviews;

        /** @param  list<Review>  $reviews */
        public function __construct(array $reviews)
        {
            $this->reviews = $reviews;
        }

        public function save(Review $review): void
        {
            foreach ($this->reviews as $index => $stored) {
                if ($stored->id->equals($review->id)) {
                    $this->reviews[$index] = $review;

                    return;
                }
            }

            $this->reviews[] = $review;
        }

        public function findById(ReviewId $id): ?Review
        {
            foreach ($this->reviews as $review) {
                if ($review->id->equals($id)) {
                    return $review;
                }
            }

            return null;
        }
    };
}
