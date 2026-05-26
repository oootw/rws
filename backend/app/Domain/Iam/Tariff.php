<?php

declare(strict_types=1);

namespace App\Domain\Iam;

/**
 * Тариф владельца — лёгкая read-сущность.
 * Используется как «к чему привязать платёжную транзакцию» и как маркер
 * default-тарифа (см. SetDefaultTariff). Цены/лимиты живут в persistence-модели —
 * домену пока не нужны.
 */
final readonly class Tariff
{
    public function __construct(
        public TariffId $id,
        public string $title,
        public bool $isDefault = false,
    ) {}
}
