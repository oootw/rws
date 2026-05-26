<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard-виджет: общая статистика по владельцам.
 *  - всего владельцев;
 *  - с активной подпиской (subscription_ends_at > now());
 *  - привязан Telegram (хотя бы один канал).
 */
final class OwnersStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $total = User::query()->count();
        $active = User::query()->where('subscription_ends_at', '>', now())->count();
        $withTelegram = User::query()->whereNotNull('telegram_id')->count();

        return [
            Stat::make('Владельцев', (string) $total)
                ->description('всего в системе')
                ->color('primary'),

            Stat::make('С активной подпиской', (string) $active)
                ->description($total > 0 ? round($active / $total * 100).'% от общего' : '—')
                ->color('success'),

            Stat::make('С Telegram', (string) $withTelegram)
                ->description('привязан бот')
                ->color('info'),
        ];
    }
}
