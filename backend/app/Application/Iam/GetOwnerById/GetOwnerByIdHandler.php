<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerById;

use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;

final readonly class GetOwnerByIdHandler
{
    public function __construct(
        private OwnerRepository $owners,
    ) {}

    public function handle(GetOwnerByIdQuery $query): ?Owner
    {
        return $this->owners->findById(new OwnerId($query->ownerId));
    }
}
