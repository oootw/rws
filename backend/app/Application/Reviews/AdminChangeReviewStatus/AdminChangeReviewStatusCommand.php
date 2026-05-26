<?php

declare(strict_types=1);

namespace App\Application\Reviews\AdminChangeReviewStatus;

use App\Enums\ReviewStatus;

/**
 * Команда смены статуса отзыва от имени админа. В отличие от
 * ChangeReviewStatusCommand здесь нет ownerId — авторизация
 * по факту попадания запроса в админ-панель.
 */
final readonly class AdminChangeReviewStatusCommand
{
    public function __construct(
        public string $reviewId,
        public ReviewStatus $newStatus,
    ) {}
}
