<?php

declare(strict_types=1);

namespace App\Application\Notifications\NotifyAboutNegativeReview;

use App\Application\Notifications\NotificationAction;
use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;

/**
 * Use case: уведомить владельца о новом негативном отзыве.
 * Список action'ов (в работе / решено / архив) — детально знание Notifications,
 * Reviews-контекст про конкретные лейблы и payload'ы кнопок ничего не знает.
 */
final readonly class NotifyAboutNegativeReviewHandler
{
    public function __construct(
        private OwnerNotifier $notifier,
    ) {}

    public function handle(NotifyAboutNegativeReviewCommand $command): void
    {
        $text = implode("\n", array_filter([
            'Новый негативный отзыв',
            "⭐ {$command->stars} — {$command->placeTitle}",
            $command->reviewText,
            "Контакт: {$command->reviewerContact}",
        ]));

        $this->notifier->notify(new OwnerNotification(
            contact: $command->contact,
            text: $text,
            emailSubject: 'Новый негативный отзыв',
            actions: [
                new NotificationAction('В работе', "review:{$command->reviewId}:in_progress"),
                new NotificationAction('Решено', "review:{$command->reviewId}:resolved"),
                new NotificationAction('Архив', "review:{$command->reviewId}:archived"),
            ],
        ));
    }
}
