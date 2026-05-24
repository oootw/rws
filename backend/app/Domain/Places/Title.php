<?php

declare(strict_types=1);

namespace App\Domain\Places;

use InvalidArgumentException;

final readonly class Title
{
    private const MAX_LENGTH = 255;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('Название точки не может быть пустым.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Название не должно превышать '.self::MAX_LENGTH.' символов.'
            );
        }
    }
}
