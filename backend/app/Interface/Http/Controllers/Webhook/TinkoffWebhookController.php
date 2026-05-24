<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Webhook;

use App\Application\Payments\Exceptions\InvalidPaymentNotification;
use App\Application\Payments\Exceptions\PaymentTransactionNotFound;
use App\Application\Payments\HandlePaymentNotification\HandlePaymentNotificationCommand;
use App\Application\Payments\HandlePaymentNotification\HandlePaymentNotificationHandler;
use App\Domain\Payments\PaymentAmountMismatch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class TinkoffWebhookController
{
    public function __invoke(
        Request $request,
        HandlePaymentNotificationHandler $handler,
    ): Response {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        try {
            $handler->handle(new HandlePaymentNotificationCommand($payload));
        } catch (InvalidPaymentNotification|PaymentTransactionNotFound|PaymentAmountMismatch $exception) {
            Log::warning('Tinkoff webhook rejected', [
                'message' => $exception->getMessage(),
            ]);

            return response('INVALID', 400);
        }

        return response('OK');
    }
}
