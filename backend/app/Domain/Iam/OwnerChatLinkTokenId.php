<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

final readonly class OwnerChatLinkTokenId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('OwnerChatLinkTokenId не может быть пустым.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
