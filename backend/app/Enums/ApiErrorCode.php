<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case TenantNotFound = 'tenant_not_found';
    case PlaceNotFound = 'place_not_found';
    case ReviewNotFound = 'review_not_found';
    case SubscriptionExpired = 'subscription_expired';
    case PlatformNotFound = 'platform_not_found';
    case LoginCodeInvalid = 'login_code_invalid';
    case LoginCodeExpired = 'login_code_expired';
    case LoginCodeAlreadyConsumed = 'login_code_already_consumed';
    case SessionTenantMismatch = 'session_tenant_mismatch';
    case OwnerNotLinkedToTelegram = 'owner_not_linked_to_telegram';
    case FeatureNotAvailable = 'feature_not_available';
    case PushSubscriptionNotFound = 'push_subscription_not_found';
    case TelegramChatNotFound = 'telegram_chat_not_found';

    public function message(): string
    {
        return match ($this) {
            self::TenantNotFound => 'Аккаунт не найден. Проверьте адрес сайта.',
            self::PlaceNotFound => 'Заведение не найдено.',
            self::ReviewNotFound => 'Отзыв не найден.',
            self::SubscriptionExpired => 'Сервис временно недоступен. Подписка не активна.',
            self::PlatformNotFound => 'Площадка для отзыва не найдена.',
            self::LoginCodeInvalid => 'Код не найден. Запросите новый через /login в Telegram.',
            self::LoginCodeExpired => 'Срок действия кода истёк. Запросите новый через /login в Telegram.',
            self::LoginCodeAlreadyConsumed => 'Этот код уже использован. Запросите новый через /login в Telegram.',
            self::SessionTenantMismatch => 'Сессия не принадлежит этому кабинету.',
            self::OwnerNotLinkedToTelegram => 'Telegram не привязан. Привяжите аккаунт через бот.',
            self::FeatureNotAvailable => 'Эта функция недоступна на вашем тарифе.',
            self::PushSubscriptionNotFound => 'Push-подписка не найдена.',
            self::TelegramChatNotFound => 'Telegram-чат не найден.',
        };
    }
}
