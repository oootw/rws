<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Public;

use App\Application\Reviews\SubmitReview\SubmitReviewHandler;
use App\Domain\Places\Place;
use App\Http\Controllers\Controller;
use App\Interface\Http\Requests\Public\SubmitReviewRequest;
use Illuminate\Http\JsonResponse;

/**
 * Тонкий контроллер: достаёт уже разрешённый Place из атрибутов запроса
 * (положен middleware'ом resolve.public.place), переводит запрос в команду
 * и вызывает один use case. Никакой бизнес-логики здесь нет.
 */
final class SubmitReviewController extends Controller
{
    public function __invoke(
        SubmitReviewRequest $request,
        SubmitReviewHandler $handler,
    ): JsonResponse {
        /** @var Place $place */
        $place = $request->attributes->get('resolved_place');

        $handler->handle($request->toCommand($place));

        return response()->json(['ok' => true]);
    }
}
