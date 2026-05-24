<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case TenantNotFound = 'tenant_not_found';
    case PlaceNotFound = 'place_not_found';
    case SubscriptionExpired = 'subscription_expired';
    case PlatformNotFound = 'platform_not_found';

    public function message(): string
    {
        return match ($this) {
            self::TenantNotFound => 'Аккаунт не найден. Проверьте адрес сайта.',
            self::PlaceNotFound => 'Заведение не найдено.',
            self::SubscriptionExpired => 'Сервис временно недоступен. Подписка не активна.',
            self::PlatformNotFound => 'Площадка для отзыва не найдена.',
        };
    }
}
