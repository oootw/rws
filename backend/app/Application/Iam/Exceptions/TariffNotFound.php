<?php

declare(strict_types=1);

namespace App\Application\Iam\Exceptions;

use RuntimeException;

final class TariffNotFound extends RuntimeException
{
    public function __construct(public readonly string $tariffId)
    {
        parent::__construct("Тариф «{$tariffId}» не найден.");
    }
}
