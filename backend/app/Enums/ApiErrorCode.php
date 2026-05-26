<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case TenantNotFound = 'tenant_not_found';
    case PlaceNotFound = 'place_not_found';
    case SubscriptionExpired = 'subscription_expired';
    case PlatformNotFound = 'platform_not_found';
    case LoginCodeInvalid = 'login_code_invalid';
    case LoginCodeExpired = 'login_code_expired';
    case LoginCodeAlreadyConsumed = 'login_code_already_consumed';
    case SessionTenantMismatch = 'session_tenant_mismatch';

    public function message(): string
    {
        return match ($this) {
            self::TenantNotFound => 'Аккаунт не найден. Проверьте адрес сайта.',
            self::PlaceNotFound => 'Заведение не найдено.',
            self::SubscriptionExpired => 'Сервис временно недоступен. Подписка не активна.',
            self::PlatformNotFound => 'Площадка для отзыва не найдена.',
            self::LoginCodeInvalid => 'Код не найден. Запросите новый через /login в Telegram.',
            self::LoginCodeExpired => 'Срок действия кода истёк. Запросите новый через /login в Telegram.',
            self::LoginCodeAlreadyConsumed => 'Этот код уже использован. Запросите новый через /login в Telegram.',
            self::SessionTenantMismatch => 'Сессия не принадлежит этому кабинету.',
        };
    }
}
