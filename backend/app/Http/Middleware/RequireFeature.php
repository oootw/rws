<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesHandler;
use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesQuery;
use App\Domain\Iam\Feature;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Гард фичи по тарифу. Применяется ПОСЛЕ `subscription.active:402`:
 * если подписка не активна — выдаём 402, если активна но фичи нет — 403.
 *
 * Использование в маршруте: `->middleware('feature:multiple_places')`.
 * Ключ — backing-value из {@see Feature}.
 */
final readonly class RequireFeature
{
    public function __construct(
        private GetOwnerFeaturesHandler $features,
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $feature = Feature::tryFrom($featureKey);

        if ($feature === null) {
            throw new InvalidArgumentException("Unknown feature key: {$featureKey}");
        }

        $ownerId = CurrentOwnerId::fromRequest($request);
        $granted = $this->features->handle(new GetOwnerFeaturesQuery(ownerId: $ownerId->value));

        if (! in_array($feature, $granted, true)) {
            return ApiResponse::error(ApiErrorCode::FeatureNotAvailable, 403);
        }

        return $next($request);
    }
}
