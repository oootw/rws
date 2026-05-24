<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Tinkoff;

use App\Application\Payments\Exceptions\InvalidPaymentNotification;
use App\Application\Payments\NotificationOutcome;
use App\Application\Payments\PaymentNotification;
use App\Application\Payments\PaymentNotificationParser;

/**
 * Превращает webhook Тинькофф в доменное событие. Проверяет подпись и
 * минимально необходимую структуру; интерпретирует Status в NotificationOutcome.
 */
final readonly class TinkoffNotificationParser implements PaymentNotificationParser
{
    private const REJECTED_STATUSES = ['REJECTED', 'CANCELED', 'DEADLINE_EXPIRED', 'REFUNDED'];

    public function __construct(
        private TinkoffConfig $config,
        private TinkoffTokenSigner $signer,
    ) {}

    public function parse(array $payload): PaymentNotification
    {
        $this->verifySignature($payload);

        $orderId = $payload['OrderId'] ?? null;

        if (! is_string($orderId) || $orderId === '') {
            throw new InvalidPaymentNotification('Missing OrderId.');
        }

        $amount = isset($payload['Amount']) ? (int) $payload['Amount'] : 0;
        $externalId = isset($payload['PaymentId']) ? (string) $payload['PaymentId'] : null;
        $status = $payload['Status'] ?? null;

        return new PaymentNotification(
            transactionId: $orderId,
            outcome: $this->resolveOutcome(
                status: is_string($status) ? $status : '',
                success: ($payload['Success'] ?? false) === true,
            ),
            amountMinorUnits: $amount,
            externalId: $externalId,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function verifySignature(array $payload): void
    {
        $secret = $this->config->secretKey();

        if ($secret === null) {
            throw new InvalidPaymentNotification('Tinkoff secret key is not configured.');
        }

        if (($payload['TerminalKey'] ?? null) !== $this->config->terminalKey()) {
            throw new InvalidPaymentNotification('Terminal key mismatch.');
        }

        $token = $payload['Token'] ?? null;

        if (! is_string($token) || $token === '' || ! $this->signer->matches($payload, $secret, $token)) {
            throw new InvalidPaymentNotification('Invalid Tinkoff notification signature.');
        }
    }

    private function resolveOutcome(string $status, bool $success): NotificationOutcome
    {
        if ($status === 'CONFIRMED' && $success) {
            return NotificationOutcome::Confirmed;
        }

        if (in_array($status, self::REJECTED_STATUSES, true)) {
            return NotificationOutcome::Rejected;
        }

        return NotificationOutcome::Pending;
    }
}
