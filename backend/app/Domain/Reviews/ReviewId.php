<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

use InvalidArgumentException;

final readonly class ReviewId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('ReviewId не может быть пустым.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
