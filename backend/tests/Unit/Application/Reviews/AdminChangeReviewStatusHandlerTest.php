<?php

declare(strict_types=1);

use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusCommand;
use App\Application\Reviews\AdminChangeReviewStatus\AdminChangeReviewStatusHandler;
use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Enums\ReviewStatus;

it('меняет статус отзыва без проверки владельца', function (): void {
    $review = restoredReview(ReviewStatus::New);
    $reviews = fakeReviewsRepository([$review]);

    (new AdminChangeReviewStatusHandler($reviews))->handle(new AdminChangeReviewStatusCommand(
        reviewId: $review->id->value,
        newStatus: ReviewStatus::Resolved,
    ));

    expect($reviews->reviews[0]->status())->toBe(ReviewStatus::Resolved);
});

it('бросает ReviewNotFound для неизвестного отзыва', function (): void {
    (new AdminChangeReviewStatusHandler(fakeReviewsRepository()))->handle(
        new AdminChangeReviewStatusCommand(
            reviewId: '00000000-0000-0000-0000-000000000000',
            newStatus: ReviewStatus::Archived,
        ),
    );
})->throws(ReviewNotFound::class);
