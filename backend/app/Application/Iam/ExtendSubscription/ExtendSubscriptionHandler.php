<?php

declare(strict_types=1);

namespace App\Application\Iam\ExtendSubscription;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Shared\Clock\Clock;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Use case: продлить подписку владельца. По умолчанию — на N дней из конфига
 * (`guardreviews.subscription.duration_days`); вызывающий может явно указать
 * другую длительность (например, в платёжном вебхуке за разные тарифы).
 *
 * Возвращает обновлённый агрегат — вызывающий может тут же взять контакт
 * для уведомления "подписка продлена".
 */
final readonly class ExtendSubscriptionHandler
{
    private const DEFAULT_DURATION_DAYS = 30;

    public function __construct(
        private OwnerRepository $owners,
        private Clock $clock,
        private Config $config,
    ) {}

    public function handle(ExtendSubscriptionCommand $command): Owner
    {
        $owner = $this->owners->findById(new OwnerId($command->ownerId));

        if ($owner === null) {
            throw new TenantNotFound;
        }

        $days = $command->durationDays
            ?? (int) $this->config->get('guardreviews.subscription.duration_days', self::DEFAULT_DURATION_DAYS);

        $owner->extendSubscription($days, $this->clock->now());

        $this->owners->save($owner);

        return $owner;
    }
}
