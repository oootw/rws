<?php

declare(strict_types=1);

namespace App\Application\Reviews\DeleteReview;

/**
 * Сырой ввод: id отзыва и опциональный комментарий админа (что за спам,
 * почему удалили — попадёт в metadata записи action_logs).
 */
final readonly class DeleteReviewCommand
{
    public function __construct(
        public string $reviewId,
        public ?string $reason = null,
    ) {}
}
