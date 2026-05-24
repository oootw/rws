<?php

namespace App\Support;

use Illuminate\Http\Request;

final class TenantResolver
{
    public function resolveSlug(Request $request): ?string
    {
        $host = $request->getHost();
        $domain = config('guardreviews.domain');

        if ($host !== $domain && str_ends_with($host, '.'.$domain)) {
            $slug = substr($host, 0, -strlen('.'.$domain));

            return $slug !== '' ? $slug : null;
        }

        if ($request->hasHeader('X-Tenant-Slug') && app()->environment('local', 'testing', 'staging')) {
            return $request->header('X-Tenant-Slug');
        }

        return null;
    }
}
