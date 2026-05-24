<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\Analytics\RecordAction\RecordActionCommand;
use App\Application\Analytics\RecordAction\RecordActionHandler;
use App\Domain\Analytics\ActionType;
use App\Domain\Places\Place;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ScanController extends Controller
{
    public function store(RecordActionHandler $recordAction): JsonResponse
    {
        /** @var Place $place */
        $place = request()->attributes->get('resolved_place');

        $recordAction->handle(new RecordActionCommand(
            placeId: $place->id->value,
            type: ActionType::Scanned,
        ));

        return response()->json(['ok' => true]);
    }
}
