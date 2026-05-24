<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

final readonly class TelegramId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('TelegramId не может быть пустым.');
        }
    }
}
