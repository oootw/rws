<?php

declare(strict_types=1);

namespace App\Domain\Admin;

use InvalidArgumentException;

final readonly class AdminActionLogId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('AdminActionLogId не может быть пустым.');
        }
    }
}
