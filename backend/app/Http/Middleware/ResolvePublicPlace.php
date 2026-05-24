<?php

namespace App\Http\Middleware;

use App\Application\Places\Exceptions\PlaceUnavailable;
use App\Application\Places\ResolvePublicPlace\ResolvePublicPlaceHandler;
use App\Application\Places\ResolvePublicPlace\ResolvePublicPlaceQuery;
use App\Domain\Iam\Owner;
use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Резолвит публичный Place по {place} в маршруте и кладёт доменный
 * агрегат в атрибуты запроса под ключ 'resolved_place' — все дальнейшие
 * хендлеры берут уже готовый агрегат.
 */
final class ResolvePublicPlace
{
    public function __construct(
        private readonly ResolvePublicPlaceHandler $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $placeId = $request->route('place');

        /** @var Owner|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if (! is_string($placeId) || $tenant === null) {
            return ApiResponse::error(ApiErrorCode::PlaceNotFound, 404);
        }

        try {
            $place = $this->resolver->handle(new ResolvePublicPlaceQuery(
                placeId: $placeId,
                ownerId: $tenant->id->value,
            ));
        } catch (PlaceUnavailable) {
            return ApiResponse::error(ApiErrorCode::PlaceNotFound, 404);
        }

        $request->attributes->set('resolved_place', $place);

        return $next($request);
    }
}
