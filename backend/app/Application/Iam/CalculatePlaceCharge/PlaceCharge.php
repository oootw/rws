<?php

declare(strict_types=1);

namespace App\Application\Iam\CalculatePlaceCharge;

/**
 * Сводка по доплате за добавление следующей точки.
 *
 *  - `prorataAmount` — сколько доплатить сейчас за оставшиеся дни (копейки);
 *    `0`, если подписка не активна или extraPlacePrice=0 (включена в base).
 *  - `daysLeft` — сколько дней до конца текущей подписки.
 *  - `monthlyDelta` — сколько добавится к ежемесячной сумме со следующего
 *    продления (для UI «со след. месяца будет +X ₽»).
 *  - `requiresPayment` — нужно ли инициировать платёж до активации точки.
 */
final readonly class PlaceCharge
{
    public function __construct(
        public int $prorataAmount,
        public int $daysLeft,
        public int $monthlyDelta,
        public bool $requiresPayment,
    ) {}
}
