<?php

declare(strict_types=1);

namespace App\Application\Reviews\Listeners;

use App\Application\Analytics\RecordAction\RecordActionCommand;
use App\Application\Analytics\RecordAction\RecordActionHandler;
use App\Domain\Analytics\ActionType;
use App\Domain\Reviews\Events\NegativeReviewSubmitted;
use App\Jobs\SendNegativeReviewAlert;

/**
 * Реакция на доменное событие "оставлен негативный отзыв":
 *  - синхронно фиксируем действие в журнале (RecordAction use case),
 *  - асинхронно отправляем уведомление владельцу (отдельная очередь, отдельный отказ).
 *
 * Listener ничего не знает ни про SQL, ни про Telegram — только про два use case'а.
 */
final readonly class NotifyOwnerAboutNegativeReview
{
    public function __construct(
        private RecordActionHandler $recordAction,
    ) {}

    public function handle(NegativeReviewSubmitted $event): void
    {
        $this->recordAction->handle(new RecordActionCommand(
            placeId: $event->placeId->value,
            type: ActionType::LeftNegative,
        ));

        SendNegativeReviewAlert::dispatch($event->reviewId->value);
    }
}
