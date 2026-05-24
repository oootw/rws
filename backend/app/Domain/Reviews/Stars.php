<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

use InvalidArgumentException;

/**
 * Оценка от 1 до 5 звёзд. Отзывы 1-3 считаются негативными
 * (бизнес-правило: только такие принимаются на форме обратной связи).
 */
final readonly class Stars
{
    private const MIN = 1;

    private const MAX = 5;

    private const NEGATIVE_THRESHOLD = 3;

    public function __construct(public int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new InvalidArgumentException(
                'Оценка должна быть в диапазоне '.self::MIN.'..'.self::MAX.", получено: {$value}."
            );
        }
    }

    public function isNegative(): bool
    {
        return $this->value <= self::NEGATIVE_THRESHOLD;
    }
}
