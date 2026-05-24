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

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Owner|null $tenant */
        $tenant = $request->attributes->get('tenant');

        if ($tenant === null || ! $tenant->hasActiveSubscriptionAt($this->clock->now())) {
            return ApiResponse::error(ApiErrorCode::SubscriptionExpired, 403);
        }

        return $next($request);
    }
}
