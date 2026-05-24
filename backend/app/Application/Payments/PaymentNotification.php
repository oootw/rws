<?php

declare(strict_types=1);

namespace App\Application\Payments;

/**
 * Бизнес-представление webhook'а эквайера: уже разобрано и провалидировано
 * по подписи на уровне адаптера/use case'а. В пределах одного интегратора
 * (Tinkoff) этого хватает; если появятся другие провайдеры, выделим интерфейс.
 */
final readonly class PaymentNotification
{
    public function __construct(
        public string $transactionId,
        public NotificationOutcome $outcome,
        public int $amountMinorUnits,
        public ?string $externalId,
    ) {}
}
