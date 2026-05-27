<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Iam\Exceptions\OwnerNotLinkedToTelegram;
use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdQuery;
use App\Application\Iam\RequestOwnerLogin\RequestOwnerLoginCommand;
use App\Application\Iam\RequestOwnerLogin\RequestOwnerLoginHandler;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileCommand;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileHandler;
use App\Domain\Iam\Owner;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Requests\Owner\UpdateOwnerProfileRequest;
use App\Interface\Http\Views\Owner\OwnerMeView;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class OwnerProfileController
{
    public function __construct(
        private readonly GetOwnerByIdHandler $getOwner,
        private readonly UpdateOwnerProfileHandler $updateProfile,
        private readonly RequestOwnerLoginHandler $requestLogin,
    ) {}

    public function update(UpdateOwnerProfileRequest $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);
        $owner = $this->loadOwner($ownerId->value);

        if ($owner === null) {
            return ApiResponse::error(ApiErrorCode::TenantNotFound, 404);
        }

        try {
            $this->updateProfile->handle(new UpdateOwnerProfileCommand(
                ownerId: $owner->id->value,
                name: (string) $request->input('name'),
                email: (string) $request->input('email'),
                subdomain: (string) $request->input('subdomain'),
                telegramId: $owner->telegramId()?->value,
                tariffId: $owner->tariffId()?->value,
            ));
        } catch (SubdomainAlreadyTaken $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['subdomain' => [$e->getMessage()]],
            ], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $updated = $this->loadOwner($owner->id->value);

        return response()->json(['data' => OwnerMeView::fromOwner($updated ?? $owner)]);
    }

    public function issueTelegramCode(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);
        $owner = $this->loadOwner($ownerId->value);

        if ($owner === null) {
            return ApiResponse::error(ApiErrorCode::TenantNotFound, 404);
        }

        $telegramId = $owner->telegramId();

        if ($telegramId === null) {
            return ApiResponse::error(ApiErrorCode::OwnerNotLinkedToTelegram, 422);
        }

        try {
            $issued = $this->requestLogin->handle(
                new RequestOwnerLoginCommand(telegramId: $telegramId->value),
            );
        } catch (OwnerNotLinkedToTelegram) {
            return ApiResponse::error(ApiErrorCode::OwnerNotLinkedToTelegram, 422);
        }

        return response()->json([
            'data' => [
                'code' => $issued->code,
                'expires_at' => $issued->expiresAt->format(DATE_ATOM),
            ],
        ]);
    }

    private function loadOwner(string $ownerId): ?Owner
    {
        return $this->getOwner->handle(new GetOwnerByIdQuery(ownerId: $ownerId));
    }
}
