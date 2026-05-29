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

    public static function chatLinked(): string
    {
        return 'Чат привязан к вашему аккаунту Guard Reviews. '.
            'Сюда будут приходить уведомления о негативных отзывах.';
    }

    public static function chatLinkInvalid(): string
    {
        return 'Ссылка для привязки недействительна или устарела. '.
            'Сгенерируйте новую в панели владельца.';
    }

    public static function chatLinkHint(): string
    {
        return 'Я готов присылать сюда уведомления о негативных отзывах. '.
            'Чтобы привязать этот чат к вашему аккаунту, откройте панель '.
            'владельца Guard Reviews и нажмите «Привязать Telegram-чат».';
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
