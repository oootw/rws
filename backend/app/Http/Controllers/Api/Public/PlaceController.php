<?php

namespace App\Http\Controllers\Api\Public;

use App\Application\Places\GetPublicPlaceView\GetPublicPlaceViewHandler;
use App\Domain\Places\Place;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlaceController extends Controller
{
    public function show(
        Request $request,
        GetPublicPlaceViewHandler $getView,
    ): JsonResponse {
        /** @var Place $place */
        $place = $request->attributes->get('resolved_place');

        $view = $getView->handle($place);

        return response()->json([
            'data' => [
                'id' => $view->id,
                'title' => $view->title,
                'background_image_url' => $view->backgroundImageUrl,
                'platforms' => $view->platforms,
                'subscription_active' => true,
                'captcha_client_key' => config('guardreviews.captcha.client_key'),
                'privacy_url' => url('/privacy'),
            ],
        ]);
    }
}
