<?php

declare(strict_types=1);

namespace App\Application\Notifications\ConfirmSubscriptionRenewed;

use App\Application\Notifications\OwnerNotification;
use App\Application\Notifications\OwnerNotifier;
use DateTimeImmutable;
use DateTimeZone;

final readonly class ConfirmSubscriptionRenewedHandler
{
    private const DISPLAY_TIMEZONE = 'Europe/Moscow';

    public function __construct(
        private OwnerNotifier $notifier,
    ) {}

    public function handle(ConfirmSubscriptionRenewedCommand $command): void
    {
        $text = implode("\n", [
            'Подписка продлена!',
            $this->statusLine($command->newExpiresAt),
        ]);

        $this->notifier->notify(new OwnerNotification(
            contact: $command->contact,
            text: $text,
            emailSubject: 'Подписка Guard Reviews продлена',
        ));
    }

    private function statusLine(?DateTimeImmutable $endsAt): string
    {
        if ($endsAt === null) {
            return 'Подписка не активна. Оплатите через /pay, чтобы QR-коды снова работали.';
        }

        $formatted = $endsAt
            ->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE))
            ->format('d.m.Y H:i');

        return "Подписка активна до {$formatted} (МСК).";
    }
}
