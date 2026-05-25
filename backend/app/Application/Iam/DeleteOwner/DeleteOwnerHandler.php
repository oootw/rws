<?php

declare(strict_types=1);

namespace App\Application\Iam\DeleteOwner;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;

/**
 * Use case: админ полностью удаляет владельца.
 *
 * Поведение каскада:
 *  - places.user_id ON DELETE CASCADE → точки и их отзывы уйдут с владельцем;
 *  - payment_transactions.user_id → каскад тоже описан в миграциях;
 *  - action_logs.user_id → каскад в миграциях.
 *
 * Если владельца уже нет — TenantNotFound, чтобы админка отрапортовала
 * чётко (двойной клик / устаревшая страница).
 */
final readonly class DeleteOwnerHandler
{
    public function __construct(
        private OwnerRepository $owners,
    ) {}

    public function handle(DeleteOwnerCommand $command): void
    {
        $id = new OwnerId($command->ownerId);

        if ($this->owners->findById($id) === null) {
            throw new TenantNotFound;
        }

        $this->owners->delete($id);
    }
}
