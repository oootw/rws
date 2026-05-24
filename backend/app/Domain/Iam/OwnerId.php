<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

/**
 * Идентификатор владельца. Живёт в Iam-контексте, но используется
 * другими контекстами по ID (cross-aggregate reference).
 */
final readonly class OwnerId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('OwnerId не может быть пустым.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
