<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner\Auth;

use App\Application\Iam\Exceptions\LoginCodeNotFound;
use App\Application\Iam\ExchangeOwnerLoginCode\ExchangeOwnerLoginCodeHandler;
use App\Domain\Iam\LoginCodeAlreadyConsumed;
use App\Domain\Iam\LoginCodeExpired;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerRepository;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Requests\Owner\ExchangeLoginCodeRequest;
use App\Interface\Http\Views\Owner\OwnerMeView;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;

/**
 * `POST /api/owner/auth/exchange` — обмен 6-значного кода (из бота) на cookie-сессию.
 *
 * Проверка cross-tenant: code мог быть выдан на одном тенанте, но запрос пришёл
 * на поддомен другого. Это запрещаем явно — иначе CSRF между тенантами.
 */
final class ExchangeOwnerLoginCodeController
{
    public function __construct(
        private readonly ExchangeOwnerLoginCodeHandler $exchange,
        private readonly OwnerRepository $owners,
        private readonly AuthManager $auth,
    ) {}

    public function __invoke(ExchangeLoginCodeRequest $request): JsonResponse
    {
        try {
            $ownerId = $this->exchange->handle($request->toCommand());
        } catch (LoginCodeNotFound) {
            return ApiResponse::error(ApiErrorCode::LoginCodeInvalid, 422);
        } catch (LoginCodeExpired) {
            return ApiResponse::error(ApiErrorCode::LoginCodeExpired, 422);
        } catch (LoginCodeAlreadyConsumed) {
            return ApiResponse::error(ApiErrorCode::LoginCodeAlreadyConsumed, 422);
        }

        $owner = $this->owners->findById($ownerId);
        $tenant = $request->attributes->get('tenant');

        if ($owner === null || ! $tenant instanceof Owner || ! $owner->subdomainEquals($tenant->subdomain())) {
            return ApiResponse::error(ApiErrorCode::SessionTenantMismatch, 403);
        }

        $this->auth->guard('owner')->loginUsingId($ownerId->value);
        $request->session()->regenerate();

        return response()->json(['data' => OwnerMeView::fromOwner($owner)]);
    }
}
