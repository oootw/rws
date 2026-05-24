<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerById;

final readonly class GetOwnerByIdQuery
{
    public function __construct(public string $ownerId) {}
}
