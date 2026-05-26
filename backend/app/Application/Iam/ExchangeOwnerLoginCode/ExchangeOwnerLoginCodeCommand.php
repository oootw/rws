<?php

declare(strict_types=1);

namespace App\Application\Iam\ExchangeOwnerLoginCode;

final readonly class ExchangeOwnerLoginCodeCommand
{
    public function __construct(public string $code) {}
}
