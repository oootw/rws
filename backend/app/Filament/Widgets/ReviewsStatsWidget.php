<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ReviewStatus;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard-виджет: распределение отзывов по статусам.
 * Помогает модератору понять, есть ли «новые» отзывы, требующие реакции.
 */
final class ReviewsStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 20;

    protected function getStats(): array
    {
        $counts = Review::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $new = (int) ($counts[ReviewStatus::New->value] ?? 0);
        $inProgress = (int) ($counts[ReviewStatus::InProgress->value] ?? 0);
        $resolved = (int) ($counts[ReviewStatus::Resolved->value] ?? 0);

        return [
            Stat::make('Новые', (string) $new)
                ->description('требуют реакции')
                ->color($new > 0 ? 'danger' : 'success'),

            Stat::make('В работе', (string) $inProgress)->color('warning'),

            Stat::make('Решено', (string) $resolved)->color('success'),
        ];
    }
}
