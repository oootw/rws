<?php

namespace App\Http\Middleware;

use App\Domain\Iam\Owner;
use App\Domain\Shared\Clock\Clock;
use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSubscriptionActive
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    /**
     * Гард на активную подписку. `$status` управляет HTTP-кодом отказа:
     * public scan API исторически использует 403, owner-панель — 402
     * (Payment Required) с UX-сигналом «иди оплати».
     */
    public function handle(Request $request, Closure $next, int|string $status = 403): Response
    {
        /** @var Owner|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if ($tenant === null || ! $tenant->hasActiveSubscriptionAt($this->clock->now())) {
            return ApiResponse::error(ApiErrorCode::SubscriptionExpired, (int) $status);
        }

        return $next($request);
    }
}
