<?php

declare(strict_types=1);

use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Application\Reviews\ResendNegativeReviewAlert\ResendNegativeReviewAlertCommand;
use App\Application\Reviews\ResendNegativeReviewAlert\ResendNegativeReviewAlertHandler;

it('бросает ReviewNotFound для неизвестного отзыва', function (): void {
    (new ResendNegativeReviewAlertHandler(fakeReviewsRepository()))->handle(
        new ResendNegativeReviewAlertCommand(reviewId: '00000000-0000-0000-0000-000000000000'),
    );
})->throws(ReviewNotFound::class);
