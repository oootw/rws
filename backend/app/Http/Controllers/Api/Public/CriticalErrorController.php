<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Notifications\AlertAboutCriticalError\AlertAboutCriticalErrorCommand;
use App\Application\Notifications\AlertAboutCriticalError\AlertAboutCriticalErrorHandler;
use App\Domain\Places\Place;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreCriticalErrorRequest;
use Illuminate\Http\JsonResponse;

final class CriticalErrorController extends Controller
{
    public function store(
        StoreCriticalErrorRequest $request,
        GetOwnerByIdHandler $getOwner,
        AlertAboutCriticalErrorHandler $alert,
    ): JsonResponse {
        /** @var Place $place */
        $place = $request->attributes->get('resolved_place');
        $owner = $getOwner->handle(new GetOwnerByIdQuery(ownerId: $place->ownerId->value));

        $alert->handle(new AlertAboutCriticalErrorCommand(
            placeId: $place->id->value,
            placeTitle: $place->title()->value,
            ownerName: $owner?->name() ?? '',
            ownerEmail: $owner?->email()->value,
            ownerSubdomain: $owner?->subdomain()->value,
            context: $request->string('context')->toString(),
        ));

        return response()->json(['ok' => true]);
    }
}
