<?php

declare(strict_types=1);

namespace App\Application\Reviews\ChangeReviewStatus;

use App\Domain\Iam\OwnerId;
use App\Domain\Places\PlaceRepository;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewRepository;

/**
 * Use case: владелец меняет статус отзыва (в работе / решено / архив).
 *
 * Аутентификация владельца — выше; здесь — авторизация владения:
 * отзыв должен принадлежать точке этого владельца. Возвращаем enum,
 * чтобы вызывающий интерфейс мог отдать корректный ответ (бот, веб).
 */
final readonly class ChangeReviewStatusHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private PlaceRepository $places,
    ) {}

    public function handle(ChangeReviewStatusCommand $command): ChangeReviewStatusResult
    {
        $review = $this->reviews->findById(new ReviewId($command->reviewId));

        if ($review === null) {
            return ChangeReviewStatusResult::ReviewNotFound;
        }

        $place = $this->places->findById($review->placeId);

        if ($place === null || ! $place->isOwnedBy(new OwnerId($command->ownerId))) {
            return ChangeReviewStatusResult::NotOwnedByCaller;
        }

        $review->changeStatus($command->newStatus);
        $this->reviews->save($review);

        return ChangeReviewStatusResult::Updated;
    }
}
