<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use DateInterval;
use DateTimeImmutable;

/**
 * Подписка владельца — VO, описывающее одну точку: до какого момента
 * сервис доступен. null endsAt = "никогда не была оформлена / истекла навсегда".
 *
 * Поведение:
 *  - isActiveAt(DateTimeImmutable) — проверка на конкретный момент;
 *  - extend(days, from) — продление; если действует, продлеваем от endsAt,
 *    иначе от текущего момента ($from).
 */
final readonly class Subscription
{
    public function __construct(public ?DateTimeImmutable $endsAt = null) {}

    public static function none(): self
    {
        return new self(endsAt: null);
    }

    public function isActiveAt(DateTimeImmutable $moment): bool
    {
        return $this->endsAt !== null && $this->endsAt > $moment;
    }

    public function extend(int $days, DateTimeImmutable $now): self
    {
        $base = $this->isActiveAt($now) ? $this->endsAt : $now;

        return new self($base->add(new DateInterval('P'.$days.'D')));
    }
}
