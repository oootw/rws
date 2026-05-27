<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusCommand;
use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusHandler;
use App\Application\Reviews\ChangeReviewStatus\ChangeReviewStatusResult;
use App\Application\Reviews\ListOwnerReviews\ListOwnerReviewsHandler;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Requests\Owner\ChangeReviewStatusRequest;
use App\Interface\Http\Requests\Owner\ListOwnerReviewsRequest;
use App\Interface\Http\Views\Owner\OwnerReviewView;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class OwnerReviewsController
{
    public function __construct(
        private readonly ListOwnerReviewsHandler $listReviews,
        private readonly ChangeReviewStatusHandler $changeStatus,
    ) {}

    public function index(ListOwnerReviewsRequest $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $page = $this->listReviews->handle($request->toQuery($ownerId->value));

        return response()->json([
            'data' => array_map(OwnerReviewView::fromProjection(...), $page->items),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function changeStatus(ChangeReviewStatusRequest $request, string $review): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);
        $newStatus = $request->status();

        $result = $this->changeStatus->handle(new ChangeReviewStatusCommand(
            reviewId: $review,
            ownerId: $ownerId->value,
            newStatus: $newStatus,
        ));

        return match ($result) {
            ChangeReviewStatusResult::Updated => response()->json([
                'data' => ['id' => $review, 'status' => $newStatus->value],
            ]),
            ChangeReviewStatusResult::ReviewNotFound => ApiResponse::error(ApiErrorCode::ReviewNotFound, 404),
            ChangeReviewStatusResult::NotOwnedByCaller => ApiResponse::error(ApiErrorCode::ReviewNotFound, 404),
        };
    }
}
