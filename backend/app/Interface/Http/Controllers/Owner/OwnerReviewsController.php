<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Reviews\ListOwnerReviews\ListOwnerReviewsHandler;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Requests\Owner\ListOwnerReviewsRequest;
use App\Interface\Http\Views\Owner\OwnerReviewView;
use Illuminate\Http\JsonResponse;

final class OwnerReviewsController
{
    public function __construct(
        private readonly ListOwnerReviewsHandler $handler,
    ) {}

    public function __invoke(ListOwnerReviewsRequest $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $page = $this->handler->handle($request->toQuery($ownerId->value));

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
}
