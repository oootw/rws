<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics;

use App\Application\Analytics\GetWeeklySummary\WeeklySummaryReader;
use App\Domain\Analytics\ActionType;
use App\Domain\Analytics\WeeklySummary;
use App\Domain\Places\PlaceId;
use App\Models\ActionLog as ActionLogModel;
use DateTimeImmutable;

/**
 * Один SQL вместо загрузки записей: считает количество событий каждого типа
 * за последние 7 дней до указанного момента и собирает WeeklySummary.
 */
final class EloquentWeeklySummaryReader implements WeeklySummaryReader
{
    public function forPlace(PlaceId $placeId, DateTimeImmutable $until): WeeklySummary
    {
        $since = $until->modify('-7 days');

        $counts = ActionLogModel::query()
            ->where('place_id', $placeId->value)
            ->whereBetween('created_at', [$since, $until])
            ->selectRaw('action_type, count(*) as total')
            ->groupBy('action_type')
            ->pluck('total', 'action_type');

        return new WeeklySummary(
            scanned: (int) ($counts[ActionType::Scanned->value] ?? 0),
            redirectedExternal: (int) ($counts[ActionType::RedirectedExternal->value] ?? 0),
            leftNegative: (int) ($counts[ActionType::LeftNegative->value] ?? 0),
        );
    }
}
