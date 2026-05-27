<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Iam\GetOwnerSubscription\GetOwnerSubscriptionHandler;
use App\Application\Iam\GetOwnerSubscription\GetOwnerSubscriptionQuery;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentCommand;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentHandler;
use App\Application\Payments\ListOwnerPayments\ListOwnerPaymentsHandler;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Requests\Owner\ListOwnerPaymentsRequest;
use App\Interface\Http\Views\Owner\OwnerPaymentView;
use App\Interface\Http\Views\Owner\OwnerSubscriptionView;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OwnerSubscriptionController
{
    public function __construct(
        private readonly GetOwnerSubscriptionHandler $getSubscription,
        private readonly InitSubscriptionPaymentHandler $initPayment,
        private readonly ListOwnerPaymentsHandler $listPayments,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $view = $this->getSubscription->handle(new GetOwnerSubscriptionQuery(ownerId: $ownerId->value));

        if ($view === null) {
            return ApiResponse::error(ApiErrorCode::TenantNotFound, 404);
        }

        return response()->json(['data' => OwnerSubscriptionView::fromProjection($view)]);
    }

    public function initPayment(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $result = $this->initPayment->handle(new InitSubscriptionPaymentCommand(ownerId: $ownerId->value));

        if (! $result->isSuccessful()) {
            return response()->json(['message' => (string) $result->errorMessage], 422);
        }

        return response()->json(['data' => ['payment_url' => (string) $result->paymentUrl]]);
    }

    public function payments(ListOwnerPaymentsRequest $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $page = $this->listPayments->handle($request->toQuery($ownerId->value));

        return response()->json([
            'data' => array_map(OwnerPaymentView::fromProjection(...), $page->items),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'last_page' => $page->lastPage(),
            ],
        ]);
    }
}
