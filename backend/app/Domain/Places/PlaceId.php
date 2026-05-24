<?php

declare(strict_types=1);

namespace App\Domain\Places;

use InvalidArgumentException;

final readonly class PlaceId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('PlaceId не может быть пустым.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
