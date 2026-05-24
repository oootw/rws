<?php

declare(strict_types=1);

namespace App\Application\Payments\InitSubscriptionPayment;

final readonly class InitSubscriptionPaymentResult
{
    private function __construct(
        public ?string $paymentUrl,
        public ?string $errorMessage,
    ) {}

    public static function success(string $paymentUrl): self
    {
        return new self($paymentUrl, null);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(null, $errorMessage);
    }

    public function isSuccessful(): bool
    {
        return $this->paymentUrl !== null;
    }
}
