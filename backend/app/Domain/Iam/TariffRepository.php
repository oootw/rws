<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface TariffRepository
{
    public function findById(TariffId $id): ?Tariff;

    /**
     * Тариф «по умолчанию» — для регистрации без явного выбора и для платежей,
     * когда у владельца ещё не назначен тариф. Может вернуть null, если такого
     * тарифа в БД нет.
     */
    public function findDefault(): ?Tariff;
}
