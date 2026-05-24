<?php

declare(strict_types=1);

namespace App\Domain\Shared\Clock;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
