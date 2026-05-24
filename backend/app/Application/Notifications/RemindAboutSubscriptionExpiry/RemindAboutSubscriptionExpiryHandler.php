<?php

declare(strict_types=1);

namespace App\Application\Notifications\RemindAboutSubscriptionExpiry;

use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;
use DateTimeZone;

final readonly class RemindAboutSubscriptionExpiryHandler
{
    private const DISPLAY_TIMEZONE = 'Europe/Moscow';

    public function __construct(
        private OwnerNotifier $notifier,
    ) {}

    public function handle(RemindAboutSubscriptionExpiryCommand $command): void
    {
        $expiresAt = $command->expiresAt
            ->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE))
            ->format('d.m.Y');

        $text = implode("\n", [
            "Подписка истекает через {$command->daysBefore} дн. ({$expiresAt}).",
            'QR-коды перестанут работать после этой даты.',
            '',
            'Продлите подписку: /pay',
        ]);

        $this->notifier->notify(new OwnerNotification(
            contact: $command->contact,
            text: $text,
            emailSubject: 'Напоминание о подписке Guard Reviews',
        ));
    }
}
