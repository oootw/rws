<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerLoginRequestRepository
{
    public function save(OwnerLoginRequest $request): void;

    public function findActiveByCode(string $code): ?OwnerLoginRequest;

    public function findById(OwnerLoginRequestId $id): ?OwnerLoginRequest;
}
