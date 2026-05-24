<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\TariffId;
use LogicException;

/**
 * Aggregate root контекста Payments: одна попытка оплаты подписки.
 *
 * Жизненный цикл:
 *   start() → markInitialized() (выдан внешний ID/URL) → confirm() | fail()
 *
 * Что НЕ делает:
 *  - не общается с эквайером (это адаптер AcquirerGateway);
 *  - не продлевает подписку (это use case в Iam, дёргается уже после confirm).
 */
final class PaymentTransaction
{
    private function __construct(
        public readonly PaymentTransactionId $id,
        public readonly OwnerId $ownerId,
        public readonly TariffId $tariffId,
        private Money $amount,
        private PaymentStatus $status,
        private ?string $externalId,
    ) {}

    public static function start(
        PaymentTransactionId $id,
        OwnerId $ownerId,
        TariffId $tariffId,
        Money $amount,
    ): self {
        return new self($id, $ownerId, $tariffId, $amount, PaymentStatus::Pending, null);
    }

    public static function restore(
        PaymentTransactionId $id,
        OwnerId $ownerId,
        TariffId $tariffId,
        Money $amount,
        PaymentStatus $status,
        ?string $externalId,
    ): self {
        return new self($id, $ownerId, $tariffId, $amount, $status, $externalId);
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function isFinalized(): bool
    {
        return $this->status === PaymentStatus::Success
            || $this->status === PaymentStatus::Refunded;
    }

    public function markInitialized(string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function fail(): void
    {
        if ($this->status === PaymentStatus::Success) {
            throw new LogicException('Нельзя пометить успешный платёж как неуспешный.');
        }

        $this->status = PaymentStatus::Failed;
    }

    public function confirm(Money $reportedAmount, ?string $externalId = null): void
    {
        if (! $this->amount->equals($reportedAmount)) {
            throw new PaymentAmountMismatch;
        }

        $this->status = PaymentStatus::Success;

        if ($externalId !== null) {
            $this->externalId = $externalId;
        }
    }
}
