<?php

namespace App\Http\Middleware;

use App\Domain\Iam\Owner;
use App\Enums\ApiErrorCode;
use App\Models\User;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cross-tenant guard: если в текущей сессии залогинен owner,
 * чей subdomain не равен поддомену запроса — разлогиниваем и отдаём 403.
 *
 * Защищает от перехвата cookie между поддоменами одного apex-домена.
 * Применяется в группе `/api/owner/*` после `tenant` и до `auth:owner`.
 */
final class EnsureSessionMatchesTenant
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $guard = $this->auth->guard('owner');
        /** @var User|null $user */
        $user = $guard->user();

        if ($user === null) {
            return $next($request);
        }

        $tenant = $request->attributes->get('tenant');

        if (! $tenant instanceof Owner || (string) $user->subdomain_slug !== $tenant->subdomain()->value) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return ApiResponse::error(ApiErrorCode::SessionTenantMismatch, 403);
        }

        return $next($request);
    }
}
