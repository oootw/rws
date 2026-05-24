<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

final readonly class Email
{
    private const MAX_LENGTH = 255;

    public function __construct(public string $value)
    {
        if ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Некорректный email.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Email не должен превышать '.self::MAX_LENGTH.' символов.');
        }
    }
}
