<?php

declare(strict_types=1);

namespace App\Application\Payments;

use App\Application\Payments\Exceptions\InvalidPaymentNotification;

/**
 * Принимает «сырой» payload от эквайера, валидирует подпись/структуру
 * и возвращает доменное представление. Реализация — Tinkoff-специфичная.
 */
interface PaymentNotificationParser
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidPaymentNotification
     */
    public function parse(array $payload): PaymentNotification;
}
