<?php

declare(strict_types=1);

namespace App\Application\Iam\ResolveTenantBySubdomain;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use InvalidArgumentException;

/**
 * Находит владельца по поддомену. Если поддомен не валиден или такого
 * владельца нет — кидает TenantNotFound (одинаково для обоих случаев,
 * наружу 404 без подсказок).
 */
final readonly class ResolveTenantBySubdomainHandler
{
    public function __construct(
        private OwnerRepository $owners,
    ) {}

    public function handle(ResolveTenantBySubdomainQuery $query): Owner
    {
        try {
            $subdomain = new SubdomainSlug($query->subdomain);
        } catch (InvalidArgumentException) {
            throw new TenantNotFound;
        }

        $owner = $this->owners->findBySubdomain($subdomain);

        if ($owner === null) {
            throw new TenantNotFound;
        }

        return $owner;
    }
}
