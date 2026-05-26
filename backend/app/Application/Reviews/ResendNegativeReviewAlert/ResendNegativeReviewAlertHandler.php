<?php

declare(strict_types=1);

namespace App\Application\Reviews\ResendNegativeReviewAlert;

use App\Application\Reviews\Exceptions\ReviewNotFound;
use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewRepository;
use App\Jobs\SendNegativeReviewAlert;

/**
 * Use case: повторно поставить в очередь уведомление о негативном отзыве.
 *
 * Сценарий — отладка: владелец говорит «не пришло», админ дёргает
 * action в карточке отзыва. Job берёт actuelles контакты владельца сам,
 * поэтому здесь только проверяем существование отзыва и (де)диспатчим
 * через тот же канал, что использует listener.
 */
final readonly class ResendNegativeReviewAlertHandler
{
    public function __construct(
        private ReviewRepository $reviews,
    ) {}

    public function handle(ResendNegativeReviewAlertCommand $command): void
    {
        $id = new ReviewId($command->reviewId);

        if ($this->reviews->findById($id) === null) {
            throw new ReviewNotFound;
        }

        SendNegativeReviewAlert::dispatch($id->value);
    }
}
