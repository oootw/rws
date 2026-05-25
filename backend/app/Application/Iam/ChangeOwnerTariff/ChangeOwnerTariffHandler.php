<?php

declare(strict_types=1);

namespace App\Application\Iam\ChangeOwnerTariff;

use App\Application\Iam\Exceptions\TariffNotFound;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;

/**
 * Use case: админ меняет тариф владельцу.
 * tariffId = null допускается — означает "отвязать тариф" (например,
 * до выбора плана при ручной регистрации).
 */
final readonly class ChangeOwnerTariffHandler
{
    public function __construct(
        private OwnerRepository $owners,
        private TariffRepository $tariffs,
    ) {}

    public function handle(ChangeOwnerTariffCommand $command): void
    {
        $owner = $this->owners->findById(new OwnerId($command->ownerId));

        if ($owner === null) {
            throw new TenantNotFound;
        }

        $tariffId = null;

        if ($command->tariffId !== null && $command->tariffId !== '') {
            $candidate = new TariffId($command->tariffId);

            if ($this->tariffs->findById($candidate) === null) {
                throw new TariffNotFound($candidate->value);
            }

            $tariffId = $candidate;
        }

        $owner->changeTariff($tariffId);
        $this->owners->save($owner);
    }
}
