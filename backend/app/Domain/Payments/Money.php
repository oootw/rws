<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use InvalidArgumentException;

/**
 * Денежная сумма в минимальных единицах (копейках). Без валюты —
 * сервис работает только в рублях; если появятся другие — добавим Currency VO.
 */
final readonly class Money
{
    public function __construct(public int $minorUnits)
    {
        if ($minorUnits <= 0) {
            throw new InvalidArgumentException('Сумма должна быть положительной.');
        }
    }

    public function equals(Money $other): bool
    {
        return $this->minorUnits === $other->minorUnits;
    }
}
