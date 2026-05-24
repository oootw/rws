<?php

declare(strict_types=1);

namespace App\Application\Payments;

final readonly class InitPaymentResponse
{
    private function __construct(
        public ?string $paymentUrl,
        public ?string $externalId,
        public ?string $errorMessage,
    ) {}

    public static function success(string $paymentUrl, ?string $externalId): self
    {
        return new self($paymentUrl, $externalId, null);
    }

    public static function failure(string $errorMessage): self
    {
        return new self(null, null, $errorMessage);
    }

    public function isSuccessful(): bool
    {
        return $this->paymentUrl !== null;
    }
}
