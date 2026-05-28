<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Domain\Notifications\OwnerContact;

/**
 * Описание уведомления, которое нужно доставить владельцу.
 * Канал-независимое представление: text — общий, subject — для e-mail,
 * actions — для каналов с интерактивом. kind — машинно-читаемый тип
 * (negative_review/subscription_renewed/...) для notification_deliveries
 * и админ-аналитики.
 */
final readonly class OwnerNotification
{
    /**
     * @param  list<NotificationAction>  $actions
     * @param  string|null  $targetUrl  абсолютный или relative URL для deep-link
     *                                  каналов (push-уведомление по клику ведёт
     *                                  именно сюда). null — открыть owner-shell.
     */
    public function __construct(
        public OwnerContact $contact,
        public string $text,
        public string $emailSubject,
        public array $actions = [],
        public string $kind = 'unknown',
        public ?string $targetUrl = null,
    ) {}
}
