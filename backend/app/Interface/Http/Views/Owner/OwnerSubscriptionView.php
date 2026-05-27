<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Application\Iam\GetOwnerSubscription\OwnerSubscriptionView as SubscriptionProjection;

final readonly class OwnerSubscriptionView
{
    /**
     * @return array{
     *     tariff_id: ?string,
     *     tariff_title: ?string,
     *     ends_at: ?string,
     *     days_left: int,
     *     is_active: bool,
     *     places_used: int,
     *     places_limit: ?int,
     *     next_charge_amount: int,
     * }
     */
    public static function fromProjection(SubscriptionProjection $view): array
    {
        return [
            'tariff_id' => $view->tariffId,
            'tariff_title' => $view->tariffTitle,
            'ends_at' => $view->endsAt?->format(DATE_ATOM),
            'days_left' => $view->daysLeft,
            'is_active' => $view->isActive,
            'places_used' => $view->placesUsed,
            'places_limit' => $view->placesLimit,
            'next_charge_amount' => $view->nextChargeAmount,
        ];
    }
}
