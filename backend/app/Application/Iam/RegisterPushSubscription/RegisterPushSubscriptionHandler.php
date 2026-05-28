<?php

declare(strict_types=1);

namespace App\Application\Iam\RegisterPushSubscription;

use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerPushSubscription;
use App\Domain\Iam\OwnerPushSubscriptionIdGenerator;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\PushSubscriptionRepository;
use App\Domain\Shared\Clock\Clock;

/**
 * Use case: SPA сообщает (endpoint + ключи) — upsert по endpoint.
 *
 *  - тот же owner: обновляем lastSeenAt;
 *  - другой owner: устройство сменило хозяина → перевыпуск под текущего;
 *  - новый endpoint: регистрируем.
 *
 * Обёрнут в транзакцию: delete+save при смене владельца должны быть атомарны.
 */
final readonly class RegisterPushSubscriptionHandler
{
    public function __construct(
        private PushSubscriptionRepository $subscriptions,
        private OwnerPushSubscriptionIdGenerator $ids,
        private Clock $clock,
        private TransactionRunner $tx,
    ) {}

    public function handle(RegisterPushSubscriptionCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $endpoint = new PushSubscriptionEndpoint($command->endpoint);
        $now = $this->clock->now();

        $this->tx->run(function () use ($ownerId, $endpoint, $command, $now): void {
            $existing = $this->subscriptions->findByEndpoint($endpoint);

            if ($existing !== null && $existing->ownerId->equals($ownerId)) {
                $existing->touchLastSeen($now);
                $this->subscriptions->save($existing);

                return;
            }

            if ($existing !== null) {
                $this->subscriptions->deleteByEndpoint($endpoint);
            }

            $this->subscriptions->save(OwnerPushSubscription::register(
                id: $this->ids->next(),
                ownerId: $ownerId,
                endpoint: $endpoint,
                p256dh: $command->p256dh,
                auth: $command->auth,
                userAgent: $command->userAgent,
                now: $now,
            ));
        });
    }
}
