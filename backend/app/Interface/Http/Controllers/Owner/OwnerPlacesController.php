<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Iam\CalculatePlaceCharge\CalculatePlaceChargeHandler;
use App\Application\Iam\CalculatePlaceCharge\CalculatePlaceChargeQuery;
use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationCommand;
use App\Application\Places\ChangePlaceActivation\ChangePlaceActivationHandler;
use App\Application\Places\DeletePlace\DeletePlaceCommand;
use App\Application\Places\DeletePlace\DeletePlaceHandler;
use App\Application\Places\GetPlaceForOwner\GetPlaceForOwnerHandler;
use App\Application\Places\GetPlaceForOwner\GetPlaceForOwnerQuery;
use App\Application\Places\ListOwnerPlaces\ListOwnerPlacesHandler;
use App\Application\Places\ListOwnerPlaces\ListOwnerPlacesQuery;
use App\Application\Places\RegisterPlace\RegisterPlaceCommand;
use App\Application\Places\RegisterPlace\RegisterPlaceHandler;
use App\Application\Places\UpdatePlace\UpdatePlaceCommand;
use App\Application\Places\UpdatePlace\UpdatePlaceHandler;
use App\Domain\Iam\OwnerId;
use App\Domain\Places\Place;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Requests\Owner\SavePlaceRequest;
use App\Interface\Http\Requests\Owner\TogglePlaceRequest;
use App\Interface\Http\Views\Owner\OwnerPlaceDetailView;
use App\Interface\Http\Views\Owner\OwnerPlaceListItemView;
use App\Interface\Http\Views\Owner\PlaceChargeView;
use App\Services\QrCodeService;
use App\Support\ApiResponse;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD точек владельца. Авторизация на принадлежность точки делается
 * через GetPlaceForOwnerHandler — это явный шаг "сначала найди мою точку",
 * а потом уже зови существующий use case (которые шарятся с админкой).
 */
final class OwnerPlacesController
{
    public function __construct(
        private readonly ListOwnerPlacesHandler $listPlaces,
        private readonly GetPlaceForOwnerHandler $getPlace,
        private readonly GetOwnerByIdHandler $getOwner,
        private readonly RegisterPlaceHandler $registerPlace,
        private readonly UpdatePlaceHandler $updatePlace,
        private readonly ChangePlaceActivationHandler $changeActivation,
        private readonly DeletePlaceHandler $deletePlace,
        private readonly CalculatePlaceChargeHandler $calculateCharge,
        private readonly QrCodeService $qrCodeService,
        private readonly Config $config,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $summaries = $this->listPlaces->handle(new ListOwnerPlacesQuery(ownerId: $ownerId->value));

        return response()->json([
            'data' => array_map(OwnerPlaceListItemView::fromSummary(...), $summaries),
        ]);
    }

    public function show(Request $request, string $place): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);
        $domainPlace = $this->loadOwnedPlace($place, $ownerId);

        if ($domainPlace === null) {
            return ApiResponse::error(ApiErrorCode::PlaceNotFound, 404);
        }

        $owner = $this->getOwner->handle(new GetOwnerByIdQuery(ownerId: $ownerId->value));

        if ($owner === null) {
            return ApiResponse::error(ApiErrorCode::TenantNotFound, 404);
        }

        return response()->json([
            'data' => OwnerPlaceDetailView::build(
                place: $domainPlace,
                owner: $owner,
                qrCodeService: $this->qrCodeService,
                appDomain: (string) $this->config->get('guardreviews.domain'),
            ),
        ]);
    }

    public function chargePreview(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $charge = $this->calculateCharge->handle(new CalculatePlaceChargeQuery(ownerId: $ownerId->value));

        return response()->json(['data' => PlaceChargeView::fromCharge($charge)]);
    }

    public function store(SavePlaceRequest $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $placeId = $this->registerPlace->handle(new RegisterPlaceCommand(
            ownerId: $ownerId->value,
            title: (string) $request->input('title'),
            platforms: $request->platformsPayload(),
            backgroundImageUrl: $request->input('background_image_url'),
        ));

        $charge = $this->calculateCharge->handle(new CalculatePlaceChargeQuery(ownerId: $ownerId->value));

        return response()->json([
            'data' => ['id' => $placeId->value],
            'charge' => PlaceChargeView::fromCharge($charge),
        ], 201);
    }

    public function update(SavePlaceRequest $request, string $place): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        if ($this->loadOwnedPlace($place, $ownerId) === null) {
            return ApiResponse::error(ApiErrorCode::PlaceNotFound, 404);
        }

        $this->updatePlace->handle(new UpdatePlaceCommand(
            placeId: $place,
            title: (string) $request->input('title'),
            platforms: $request->platformsPayload(),
            backgroundImageUrl: $request->input('background_image_url'),
        ));

        return response()->json(['data' => ['id' => $place]]);
    }

    public function toggle(TogglePlaceRequest $request, string $place): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        if ($this->loadOwnedPlace($place, $ownerId) === null) {
            return ApiResponse::error(ApiErrorCode::PlaceNotFound, 404);
        }

        $this->changeActivation->handle(new ChangePlaceActivationCommand(
            placeId: $place,
            active: $request->active(),
        ));

        return response()->json(['data' => ['id' => $place, 'is_active' => $request->active()]]);
    }

    public function destroy(Request $request, string $place): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        if ($this->loadOwnedPlace($place, $ownerId) === null) {
            return ApiResponse::error(ApiErrorCode::PlaceNotFound, 404);
        }

        $this->deletePlace->handle(new DeletePlaceCommand(placeId: $place));

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function loadOwnedPlace(string $placeId, OwnerId $ownerId): ?Place
    {
        return $this->getPlace->handle(new GetPlaceForOwnerQuery(
            placeId: $placeId,
            ownerId: $ownerId->value,
        ));
    }
}
