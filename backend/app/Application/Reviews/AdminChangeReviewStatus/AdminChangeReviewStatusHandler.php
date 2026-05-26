<?php

declare(strict_types=1);

namespace App\Application\Reviews\AdminChangeReviewStatus;

use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewRepository;

/**
 * Use case: админ меняет статус отзыва (модерация). Без проверки
 * принадлежности владельцу — это привилегия админ-панели.
 */
final readonly class AdminChangeReviewStatusHandler
{
    public function __construct(
        private ReviewRepository $reviews,
    ) {}

    public function handle(AdminChangeReviewStatusCommand $command): void
    {
        $review = $this->reviews->findById(new ReviewId($command->reviewId));

        if ($review === null) {
            throw new ReviewNotFound;
        }

        $review->changeStatus($command->newStatus);
        $this->reviews->save($review);
    }
}
