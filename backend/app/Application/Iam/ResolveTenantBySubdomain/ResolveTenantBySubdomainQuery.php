<?php

declare(strict_types=1);

namespace App\Application\Iam\ResolveTenantBySubdomain;

final readonly class ResolveTenantBySubdomainQuery
{
    public function __construct(public string $subdomain) {}
}
