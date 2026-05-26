<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Application\Iam\CalculatePlaceCharge\PlaceCharge;

final readonly class PlaceChargeView
{
    /**
     * @return array{
     *     prorata_amount: int,
     *     days_left: int,
     *     monthly_delta: int,
     *     requires_payment: bool,
     * }
     */
    public static function fromCharge(PlaceCharge $charge): array
    {
        return [
            'prorata_amount' => $charge->prorataAmount,
            'days_left' => $charge->daysLeft,
            'monthly_delta' => $charge->monthlyDelta,
            'requires_payment' => $charge->requiresPayment,
        ];
    }
}
