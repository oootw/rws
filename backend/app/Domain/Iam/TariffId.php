<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

final readonly class TariffId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('TariffId не может быть пустым.');
        }
    }
}
