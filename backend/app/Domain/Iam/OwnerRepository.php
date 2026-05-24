<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerRepository
{
    public function save(Owner $owner): void;

    public function findById(OwnerId $id): ?Owner;

    public function findBySubdomain(SubdomainSlug $subdomain): ?Owner;

    public function findByTelegramId(TelegramId $telegramId): ?Owner;

    public function subdomainExists(SubdomainSlug $subdomain): bool;
}
