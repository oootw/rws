<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Analytics\GetOwnerDashboard\DailyMetric;
use App\Application\Analytics\GetOwnerDashboard\GetOwnerDashboardHandler;
use App\Application\Analytics\GetOwnerDashboard\GetOwnerDashboardQuery;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OwnerDashboardController
{
    public function __construct(
        private readonly GetOwnerDashboardHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $dashboard = $this->handler->handle(new GetOwnerDashboardQuery(ownerId: $ownerId->value));

        return response()->json([
            'data' => [
                'scans' => $dashboard->scans,
                'reviews' => $dashboard->reviews,
                'negative' => $dashboard->negative,
                'redirects' => $dashboard->redirects,
                'places_count' => $dashboard->placesCount,
                'daily_series' => array_map(
                    static fn (DailyMetric $metric): array => [
                        'date' => $metric->date,
                        'scans' => $metric->scans,
                        'reviews' => $metric->reviews,
                    ],
                    $dashboard->dailySeries,
                ),
            ],
        ]);
    }
}
