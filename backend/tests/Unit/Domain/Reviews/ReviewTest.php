<?php

declare(strict_types=1);

use App\Domain\Places\PlaceId;
use App\Domain\Reviews\ContactInfo;
use App\Domain\Reviews\Events\NegativeReviewSubmitted;
use App\Domain\Reviews\Review;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewText;
use App\Domain\Reviews\Stars;
use App\Enums\ReviewStatus;

function submitReview(int $stars = 2): Review
{
    return Review::submit(
        id: new ReviewId('11111111-1111-1111-1111-111111111111'),
        placeId: new PlaceId('22222222-2222-2222-2222-222222222222'),
        stars: new Stars($stars),
        text: new ReviewText('Долго ждали'),
        contact: new ContactInfo('+7 999 000 0000'),
        ipHash: null,
        submittedAt: new DateTimeImmutable('2026-05-24T12:00:00Z'),
    );
}

it('создаёт отзыв со статусом «новый» и записывает доменное событие', function (): void {
    $review = submitReview(stars: 1);

    expect($review->status())->toBe(ReviewStatus::New);

    $events = $review->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(NegativeReviewSubmitted::class);
});

it('после извлечения очищает накопленные события', function (): void {
    $review = submitReview();
    $review->pullRecordedEvents();

    expect($review->pullRecordedEvents())->toBe([]);
});

it('запрещает создавать через форму обратной связи положительный отзыв', function (): void {
    submitReview(stars: 5);
})->throws(DomainException::class);
