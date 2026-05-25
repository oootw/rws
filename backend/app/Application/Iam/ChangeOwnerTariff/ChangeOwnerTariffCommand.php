<?php

declare(strict_types=1);

namespace App\Application\Iam\ChangeOwnerTariff;

final readonly class ChangeOwnerTariffCommand
{
    public function __construct(
        public string $ownerId,
        public ?string $tariffId,
    ) {}
}
