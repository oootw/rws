<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Application\Payments\ListOwnerPayments\OwnerPaymentView as PaymentProjection;

final readonly class OwnerPaymentView
{
    /**
     * @return array{
     *     id: string,
     *     amount: int,
     *     status: string,
     *     external_id: ?string,
     *     tariff_title: ?string,
     *     created_at: string,
     * }
     */
    public static function fromProjection(PaymentProjection $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'status' => $payment->status->value,
            'external_id' => $payment->externalId,
            'tariff_title' => $payment->tariffTitle,
            'created_at' => $payment->createdAt->format(DATE_ATOM),
        ];
    }
}
