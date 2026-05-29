<?php

declare(strict_types=1);

namespace App\Domain\Iam;

/**
 * Фича тарифа — типизированный ключ + UI-лейбл.
 *
 * Backing-value — стабильный контракт с БД (`tariffs.features` JSON list).
 * Переименование = миграция данных через deprecation: новый case → миграция → удалить старый.
 *
 * Расширение новой фичей: добавить case + match в label() + (опционально) чекбокс в Filament.
 * Миграция БД для этого НЕ нужна.
 */
enum Feature: string
{
    case MultiplePlaces = 'multiple_places';
    case WeeklyDigest = 'weekly_digest';
    case NegativeAlertsTelegram = 'negative_alerts_telegram';
    case NegativeAlertsEmail = 'negative_alerts_email';
    case CustomBranding = 'custom_branding';
    case QrThemes = 'qr_themes';
    case CsvExportReviews = 'csv_export_reviews';
    case ApiAccess = 'api_access';
    case PrioritySupport = 'priority_support';
    case SharedTelegramChat = 'shared_telegram_chat';

    public function label(): string
    {
        return match ($this) {
            self::MultiplePlaces => 'Несколько точек',
            self::WeeklyDigest => 'Еженедельный дайджест',
            self::NegativeAlertsTelegram => 'Уведомления о негативе в Telegram',
            self::NegativeAlertsEmail => 'Уведомления о негативе на email',
            self::CustomBranding => 'Кастомный брендинг страницы',
            self::QrThemes => 'Темы оформления QR',
            self::CsvExportReviews => 'Экспорт отзывов в CSV',
            self::ApiAccess => 'Доступ к API',
            self::PrioritySupport => 'Приоритетная поддержка',
            self::SharedTelegramChat => 'Общий Telegram-чат на команду',
        };
    }
}
