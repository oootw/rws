<?php

declare(strict_types=1);

namespace App\Domain\Iam;

/**
 * Тариф владельца — лёгкая read-сущность.
 * В текущей итерации используется только как «к чему привязать платёжную транзакцию»;
 * расчёт суммы (см. CalculateSubscriptionAmountHandler) сейчас опирается на конфиг,
 * не на поля тарифа — потому здесь нет цен.
 */
final readonly class Tariff
{
    public function __construct(
        public TariffId $id,
        public string $title,
    ) {}
}
