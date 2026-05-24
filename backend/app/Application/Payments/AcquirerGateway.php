<?php

declare(strict_types=1);

namespace App\Application\Payments;

/**
 * Порт к платёжному эквайеру. Реализация — Tinkoff (или mock в тестах).
 *
 * Не возвращает HTTP-детали наверх: только бизнес-факт «удалось ли создать
 * сессию оплаты» + полезная нагрузка (URL, внешний ID).
 */
interface AcquirerGateway
{
    public function isConfigured(): bool;

    public function initSubscriptionPayment(InitPaymentRequest $request): InitPaymentResponse;
}
