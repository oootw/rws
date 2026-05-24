<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Tinkoff;

use App\Application\Payments\AcquirerGateway;
use App\Application\Payments\InitPaymentRequest;
use App\Application\Payments\InitPaymentResponse;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Адаптер платёжного шлюза «Тинькофф Эквайринг».
 *
 * Прячет всё HTTP/подпись внутри: use case видит только InitPaymentRequest →
 * InitPaymentResponse. Сетевые ошибки логируются и пробрасываются — use case
 * сам решает, как реагировать (помечает транзакцию Failed).
 */
final readonly class TinkoffAcquirerGateway implements AcquirerGateway
{
    public function __construct(
        private TinkoffConfig $config,
        private TinkoffTokenSigner $signer,
        private HttpFactory $http,
    ) {}

    public function isConfigured(): bool
    {
        return $this->config->terminalKey() !== null
            && $this->config->secretKey() !== null;
    }

    public function initSubscriptionPayment(InitPaymentRequest $request): InitPaymentResponse
    {
        try {
            $response = $this->call('Init', [
                'Amount' => $request->amount->minorUnits,
                'OrderId' => $request->transactionId->value,
                'Description' => $request->description,
                'CustomerKey' => $request->customerKey,
                'NotificationURL' => $this->config->notificationUrl(),
                'SuccessURL' => $this->config->successUrl(),
                'FailURL' => $this->config->failUrl(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Tinkoff Init failed', [
                'transaction_id' => $request->transactionId->value,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (($response['Success'] ?? false) !== true) {
            return InitPaymentResponse::failure(
                is_string($response['Message'] ?? null)
                    ? $response['Message']
                    : 'Не удалось создать платёж.',
            );
        }

        $paymentUrl = $response['PaymentURL'] ?? null;

        if (! is_string($paymentUrl) || $paymentUrl === '') {
            return InitPaymentResponse::failure('Не удалось получить ссылку на оплату.');
        }

        $externalId = isset($response['PaymentId']) ? (string) $response['PaymentId'] : null;

        return InitPaymentResponse::success($paymentUrl, $externalId);
    }

    /**
     * @param  array<string, scalar|null>  $payload
     * @return array<string, mixed>
     */
    private function call(string $method, array $payload): array
    {
        $payload['TerminalKey'] = $this->config->terminalKey();
        $payload['Token'] = $this->signer->sign($payload, (string) $this->config->secretKey());

        $response = $this->http
            ->baseUrl($this->config->apiUrl())
            ->acceptJson()
            ->asJson()
            ->post('/'.$method, $payload)
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new \RuntimeException('Invalid Tinkoff API response.');
        }

        return $response;
    }
}
