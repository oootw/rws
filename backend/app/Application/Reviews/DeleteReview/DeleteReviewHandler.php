<?php

declare(strict_types=1);

namespace App\Application\Reviews\DeleteReview;

use App\Application\Analytics\RecordAction\RecordActionCommand;
use App\Application\Analytics\RecordAction\RecordActionHandler;
use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Domain\Analytics\ActionType;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewRepository;

/**
 * Use case: админ удаляет отзыв (типично — спам).
 *
 * Перед удалением фиксируем след в action_logs (тип AdminDeletedReview)
 * с базовыми метаданными — иначе восстановить контекст уже не получится.
 * Сам отзыв строго после этого: при упавшем логировании удаления не будет.
 */
final readonly class DeleteReviewHandler
{
    public function __construct(
        private ReviewRepository $reviews,
        private RecordActionHandler $recordAction,
    ) {}

    public function handle(DeleteReviewCommand $command): void
    {
        $id = new ReviewId($command->reviewId);
        $review = $this->reviews->findById($id);

        if ($review === null) {
            throw new ReviewNotFound;
        }

        $this->recordAction->handle(new RecordActionCommand(
            placeId: $review->placeId->value,
            type: ActionType::AdminDeletedReview,
            metadata: array_filter([
                'review_id' => $id->value,
                'stars' => $review->stars->value,
                'reason' => $command->reason,
            ], static fn (mixed $v): bool => $v !== null),
        ));

        $this->reviews->delete($id);
    }
}
