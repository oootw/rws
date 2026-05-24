<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Support;

use DateTimeImmutable;
use DateTimeZone;

final class TelegramMessages
{
    private const DISPLAY_TIMEZONE = 'Europe/Moscow';

    public static function mainMenu(): string
    {
        return implode("\n", [
            'Главное меню:',
            '/places — ваши точки',
            '/addplace — добавить точку',
            '/reviews — негативные отзывы',
            '/subscription — статус подписки',
            '/pay — оплатить подписку',
            '/link — привязать MAX (скоро)',
        ]);
    }

    public static function subscriptionStatus(?DateTimeImmutable $endsAt): string
    {
        if ($endsAt === null || $endsAt <= new DateTimeImmutable) {
            return 'Подписка не активна. Оплатите через /pay, чтобы QR-коды снова работали.';
        }

        $formatted = $endsAt
            ->setTimezone(new DateTimeZone(self::DISPLAY_TIMEZONE))
            ->format('d.m.Y H:i');

        return "Подписка активна до {$formatted} (МСК).";
    }
}
