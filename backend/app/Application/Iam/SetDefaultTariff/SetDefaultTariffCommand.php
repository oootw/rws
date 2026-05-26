<?php

declare(strict_types=1);

namespace App\Application\Iam\SetDefaultTariff;

final readonly class SetDefaultTariffCommand
{
    public function __construct(
        public string $tariffId,
    ) {}
}
