<?php

declare(strict_types=1);

namespace App\Application\Payments\InitSubscriptionPayment;

final readonly class InitSubscriptionPaymentCommand
{
    public function __construct(public string $ownerId) {}
}
