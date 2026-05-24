<?php

declare(strict_types=1);

namespace App\Application\Payments\HandlePaymentNotification;

final readonly class HandlePaymentNotificationCommand
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}
}
