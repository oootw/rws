<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

final readonly class PushSubscriptionEndpoint
{
    private const MAX_LENGTH = 2048;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('Push subscription endpoint не может быть пустым.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Push subscription endpoint не должен превышать '.self::MAX_LENGTH.' символов.');
        }

        if (! str_starts_with($value, 'https://')) {
            throw new InvalidArgumentException('Push subscription endpoint должен быть https URL.');
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Push subscription endpoint должен быть валидным URL.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
