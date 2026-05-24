<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Domain\Notifications\OwnerContact;

/**
 * Описание уведомления, которое нужно доставить владельцу.
 * Канал-независимое представление: text — общий, subject — для e-mail,
 * actions — для каналов с интерактивом.
 */
final readonly class OwnerNotification
{
    /**
     * @param  list<NotificationAction>  $actions
     */
    public function __construct(
        public OwnerContact $contact,
        public string $text,
        public string $emailSubject,
        public array $actions = [],
    ) {}
}
