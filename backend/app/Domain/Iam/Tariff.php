<?php

declare(strict_types=1);

namespace App\Domain\Iam;

/**
 * Тариф владельца — лёгкая read-сущность.
 * Используется как «к чему привязать платёжную транзакцию», как маркер
 * default-тарифа (см. SetDefaultTariff) и как источник цен.
 *
 * Цены — в копейках. `extraPlacePrice` — доплата за каждую N-ю точку
 * сверх первой; редактируется супер-админом в Filament TariffResource.
 */
final readonly class Tariff
{
    public function __construct(
        public TariffId $id,
        public string $title,
        public int $basePrice = 0,
        public int $extraPlacePrice = 0,
        public bool $isDefault = false,
    ) {}
}
