<?php

namespace App\Jobs;

use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewCommand;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler;
use App\Models\Review;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Адаптер очереди: грузит отзыв + точку, спрашивает у Iam владельца,
 * вызывает Notifications use case. Бизнес-логики в job нет — только склейка.
 *
 * Надёжность доставки:
 *  - 10 попыток с экспоненциальным backoff'ом (30c → 1ч),
 *    суммарно ~1.5 часа на восстановление прокси/SMTP/бота;
 *  - после исчерпания попыток job уходит в `failed_jobs` с полным
 *    стектрейсом — `php artisan reviews:retry-failed-alerts` перезапустит.
 *  - отзыв в БД уже сохранён до постановки job в очередь — не теряется
 *    при любом сценарии падения уведомления.
 */
final class SendNegativeReviewAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public function __construct(public string $reviewId) {}

    /**
     * Экспоненциальный backoff между попытками (секунды).
     * При 10 tries это ~1.5 часа суммарно.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600, 1200, 1800, 3600, 3600];
    }

    public function handle(
        GetOwnerByIdHandler $getOwner,
        NotifyAboutNegativeReviewHandler $notify,
    ): void {
        $review = Review::query()->with('place')->find($this->reviewId);

        if ($review === null || $review->place === null) {
            return;
        }

        $owner = $getOwner->handle(new GetOwnerByIdQuery(ownerId: (string) $review->place->user_id));

        if ($owner === null) {
            return;
        }

        $notify->handle(new NotifyAboutNegativeReviewCommand(
            contact: $owner->asNotificationContact(),
            reviewId: (string) $review->id,
            placeTitle: (string) $review->place->title,
            stars: (int) $review->stars,
            reviewText: (string) $review->text,
            reviewerContact: (string) $review->contact,
        ));
    }
}
