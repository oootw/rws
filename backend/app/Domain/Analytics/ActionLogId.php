<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

use InvalidArgumentException;

final readonly class ActionLogId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('ActionLogId не может быть пустым.');
        }
    }
}
