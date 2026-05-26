<?php

declare(strict_types=1);

namespace App\Application\Iam\SetDefaultTariff;

use App\Application\Iam\Exceptions\TariffNotFound;
use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;

/**
 * Use case: назначить тариф «по умолчанию».
 *
 * Инвариант — ровно один is_default — реализуется атомарно через репозиторий:
 *  1) помечаем выбранный тариф is_default = true,
 *  2) сбрасываем флаг у всех остальных.
 *
 * Под транзакцией, чтобы между шагами никто не увидел «два default'а».
 */
final readonly class SetDefaultTariffHandler
{
    public function __construct(
        private TariffRepository $tariffs,
        private TransactionRunner $tx,
    ) {}

    public function handle(SetDefaultTariffCommand $command): void
    {
        $id = new TariffId($command->tariffId);

        if ($this->tariffs->findById($id) === null) {
            throw new TariffNotFound($id->value);
        }

        $this->tx->run(fn () => $this->tariffs->markAsOnlyDefault($id));
    }
}
