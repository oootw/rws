<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActionLogs\Schemas;

use App\Domain\Analytics\ActionType;

/**
 * Маппинг ActionType → подпись/цвет для бейджей. Вынесено в общий хелпер
 * Interface-слоя, чтобы Infolist/Table не дублировали match-блок.
 */
final class ActionLogTypeLabels
{
    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (ActionType::cases() as $case) {
            $options[$case->value] = self::for($case);
        }

        return $options;
    }

    public static function for(ActionType $type): string
    {
        return match ($type) {
            ActionType::Scanned => 'Скан QR',
            ActionType::RedirectedExternal => 'Переход на площадку',
            ActionType::LeftNegative => 'Негативный отзыв',
            ActionType::AdminDeletedReview => 'Админ удалил отзыв',
        };
    }

    public static function color(ActionType $type): string
    {
        return match ($type) {
            ActionType::Scanned => 'info',
            ActionType::RedirectedExternal => 'success',
            ActionType::LeftNegative => 'warning',
            ActionType::AdminDeletedReview => 'danger',
        };
    }
}
