<?php

declare(strict_types=1);

namespace App\Application\Iam\DeleteOwner;

final readonly class DeleteOwnerCommand
{
    public function __construct(
        public string $ownerId,
    ) {}
}
