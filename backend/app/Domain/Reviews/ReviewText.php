<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

use InvalidArgumentException;

final readonly class ReviewText
{
    private const MAX_LENGTH = 5000;

    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('Текст отзыва не может быть пустым.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Текст отзыва не должен превышать '.self::MAX_LENGTH.' символов.'
            );
        }
    }
}
