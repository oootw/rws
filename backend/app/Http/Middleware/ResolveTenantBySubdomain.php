<?php

namespace App\Http\Middleware;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\ResolveTenantBySubdomain\ResolveTenantBySubdomainHandler;
use App\Application\Iam\ResolveTenantBySubdomain\ResolveTenantBySubdomainQuery;
use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP-фасад тенант-резолва: достаёт поддомен из хоста (или X-Tenant-Slug
 * в dev-окружениях), отдаёт в use case, кладёт доменного Owner в атрибуты
 * запроса под ключ 'tenant'.
 */
final class ResolveTenantBySubdomain
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly ResolveTenantBySubdomainHandler $resolveTenant,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $this->tenantResolver->resolveSlug($request);

        if ($slug === null) {
            return ApiResponse::error(ApiErrorCode::TenantNotFound, 404);
        }

        try {
            $owner = $this->resolveTenant->handle(new ResolveTenantBySubdomainQuery(subdomain: $slug));
        } catch (TenantNotFound) {
            return ApiResponse::error(ApiErrorCode::TenantNotFound, 404);
        }

        $request->attributes->set('tenant', $owner);

        return $next($request);
    }
}
