<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\Analytics\RecordAction\RecordActionCommand;
use App\Application\Analytics\RecordAction\RecordActionHandler;
use App\Domain\Analytics\ActionType;
use App\Domain\Places\Place;
use App\Domain\Places\PlatformType;
use App\Enums\ApiErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreRedirectRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class RedirectController extends Controller
{
    public function store(
        StoreRedirectRequest $request,
        RecordActionHandler $recordAction,
    ): JsonResponse {
        /** @var Place $place */
        $place = $request->attributes->get('resolved_place');

        $platform = $place->platform(PlatformType::from($request->string('platform_type')->toString()));

        if ($platform === null) {
            return ApiResponse::error(ApiErrorCode::PlatformNotFound, 422);
        }

        $recordAction->handle(new RecordActionCommand(
            placeId: $place->id->value,
            type: ActionType::RedirectedExternal,
            metadata: ['platform' => $platform->type->value],
        ));

        return response()->json([
            'ok' => true,
            'url' => $platform->url,
        ]);
    }
}
