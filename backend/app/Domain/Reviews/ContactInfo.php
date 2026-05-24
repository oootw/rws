<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

use InvalidArgumentException;

final readonly class ContactInfo
{
    private const MAX_LENGTH = 255;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('Контакт не может быть пустым.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Контакт не должен превышать '.self::MAX_LENGTH.' символов.'
            );
        }
    }
}
