<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Analytics;

use App\Application\Analytics\GetOwnerDashboard\DailyMetric;
use App\Application\Analytics\GetOwnerDashboard\OwnerDashboardReader;
use App\Application\Analytics\GetOwnerDashboard\OwnerDashboardView;
use App\Domain\Analytics\ActionType;
use App\Domain\Iam\OwnerId;
use App\Models\ActionLog as ActionLogModel;
use App\Models\Place as PlaceModel;
use App\Models\Review as ReviewModel;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;

/**
 * Считает KPI владельца одним проходом:
 *  - суммарные счётчики по action_logs (scanned/redirected/left_negative)
 *    через JOIN с places.user_id;
 *  - кол-во отзывов через reviews JOIN places.user_id;
 *  - daily-серия (за 7 дней) для sparkline — group by date(created_at).
 *
 * Никакой N+1: каждый счётчик — один SQL.
 */
final class EloquentOwnerDashboardReader implements OwnerDashboardReader
{
    private const WINDOW_DAYS = 7;

    public function forOwner(OwnerId $ownerId, DateTimeImmutable $until): OwnerDashboardView
    {
        $since = $until->modify('-'.self::WINDOW_DAYS.' days');

        $placesCount = PlaceModel::query()->where('user_id', $ownerId->value)->count();

        $actionTotals = $this->actionTotals($ownerId, $since, $until);
        $reviews = $this->reviewsCount($ownerId, $since, $until);
        $dailySeries = $this->dailySeries($ownerId, $since, $until);

        return new OwnerDashboardView(
            scans: (int) ($actionTotals[ActionType::Scanned->value] ?? 0),
            reviews: $reviews,
            negative: (int) ($actionTotals[ActionType::LeftNegative->value] ?? 0),
            redirects: (int) ($actionTotals[ActionType::RedirectedExternal->value] ?? 0),
            placesCount: $placesCount,
            dailySeries: $dailySeries,
        );
    }

    /** @return array<string, int> */
    private function actionTotals(OwnerId $ownerId, DateTimeImmutable $since, DateTimeImmutable $until): array
    {
        return ActionLogModel::query()
            ->whereIn('place_id', $this->placeIds($ownerId))
            ->whereBetween('created_at', [$since, $until])
            ->selectRaw('action_type, count(*) as total')
            ->groupBy('action_type')
            ->pluck('total', 'action_type')
            ->map(static fn (mixed $total): int => (int) $total)
            ->all();
    }

    private function reviewsCount(OwnerId $ownerId, DateTimeImmutable $since, DateTimeImmutable $until): int
    {
        return ReviewModel::query()
            ->whereIn('place_id', $this->placeIds($ownerId))
            ->whereBetween('created_at', [$since, $until])
            ->count();
    }

    /** @return list<DailyMetric> */
    private function dailySeries(OwnerId $ownerId, DateTimeImmutable $since, DateTimeImmutable $until): array
    {
        $placeIds = $this->placeIds($ownerId);

        /** @var array<string, int> $scansByDay */
        $scansByDay = ActionLogModel::query()
            ->whereIn('place_id', $placeIds)
            ->where('action_type', ActionType::Scanned->value)
            ->whereBetween('created_at', [$since, $until])
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(static fn (mixed $total): int => (int) $total)
            ->all();

        /** @var array<string, int> $reviewsByDay */
        $reviewsByDay = ReviewModel::query()
            ->whereIn('place_id', $placeIds)
            ->whereBetween('created_at', [$since, $until])
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(static fn (mixed $total): int => (int) $total)
            ->all();

        $series = [];
        for ($offset = self::WINDOW_DAYS - 1; $offset >= 0; $offset--) {
            $date = $until->modify('-'.$offset.' days')->format('Y-m-d');
            $series[] = new DailyMetric(
                date: $date,
                scans: $scansByDay[$date] ?? 0,
                reviews: $reviewsByDay[$date] ?? 0,
            );
        }

        return $series;
    }

    private function placeIds(OwnerId $ownerId): Builder
    {
        return PlaceModel::query()
            ->where('user_id', $ownerId->value)
            ->select('id')
            ->toBase();
    }
}
