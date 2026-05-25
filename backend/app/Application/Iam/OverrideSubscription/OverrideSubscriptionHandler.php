<?php

declare(strict_types=1);

namespace App\Application\Iam\OverrideSubscription;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;

/**
 * Use case: админ выставляет произвольную дату окончания подписки
 * (или сбрасывает её). В отличие от ExtendSubscription, не накапливает срок —
 * пишет ровно ту дату, которую попросили.
 *
 * Возвращает обновлённый агрегат, чтобы интерфейс мог показать новый статус.
 */
final readonly class OverrideSubscriptionHandler
{
    public function __construct(
        private OwnerRepository $owners,
    ) {}

    public function handle(OverrideSubscriptionCommand $command): Owner
    {
        $owner = $this->owners->findById(new OwnerId($command->ownerId));

        if ($owner === null) {
            throw new TenantNotFound;
        }

        $owner->overrideSubscription($command->endsAt);
        $this->owners->save($owner);

        return $owner;
    }
}
