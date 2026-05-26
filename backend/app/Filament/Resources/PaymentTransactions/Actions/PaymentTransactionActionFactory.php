<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions\Actions;

use App\Application\Payments\ForceFailPayment\ForceFailPaymentCommand;
use App\Application\Payments\ForceFailPayment\ForceFailPaymentHandler;
use App\Application\Payments\HandlePaymentNotification\HandlePaymentNotificationCommand;
use App\Application\Payments\HandlePaymentNotification\HandlePaymentNotificationHandler;
use App\Domain\Payments\PaymentStatus;
use App\Infrastructure\Payments\Tinkoff\TinkoffConfig;
use App\Infrastructure\Payments\Tinkoff\TinkoffTokenSigner;
use App\Models\PaymentTransaction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * Custom actions для PaymentTransactionResource.
 *
 * Оба действия — отладочные:
 *  - refire_webhook: собирает корректный Tinkoff-payload по транзакции,
 *    подписывает текущим секретом и прогоняет через HandlePaymentNotificationHandler;
 *  - mark_failed: ForceFailPaymentHandler меняет статус pending → failed.
 *
 * Видимость refire_webhook ограничена не-production окружениями: симуляция
 * платежа на проде создаст артефакты у эквайера.
 */
final class PaymentTransactionActionFactory
{
    public static function refireWebhook(): Action
    {
        return Action::make('refire_webhook')
            ->label('Симулировать webhook')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (): bool => ! app()->isProduction())
            ->requiresConfirmation()
            ->modalDescription('Сформируем Tinkoff-payload по транзакции и прогоним через HandlePaymentNotificationHandler. Только dev/staging.')
            ->schema([
                Select::make('status')
                    ->label('Status в webhook')
                    ->options([
                        'CONFIRMED' => 'CONFIRMED (успех)',
                        'REJECTED' => 'REJECTED (отказ)',
                    ])
                    ->default('CONFIRMED')
                    ->required()
                    ->native(false),
            ])
            ->action(function (PaymentTransaction $record, array $data): void {
                $status = (string) $data['status'];
                $payload = self::buildSignedTinkoffPayload($record, $status);

                app(HandlePaymentNotificationHandler::class)->handle(
                    new HandlePaymentNotificationCommand(payload: $payload),
                );

                Notification::make()
                    ->title("Webhook ({$status}) обработан")
                    ->success()
                    ->send();
            });
    }

    public static function markFailed(): Action
    {
        return Action::make('mark_failed')
            ->label('Пометить как failed')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (PaymentTransaction $record): bool => $record->status === PaymentStatus::Pending)
            ->requiresConfirmation()
            ->modalDescription('Используйте для зависших pending: статус сменится на failed, дальнейшие webhook'."'".'ы по транзакции будут идемпотентно проигнорированы.')
            ->action(function (PaymentTransaction $record): void {
                app(ForceFailPaymentHandler::class)->handle(
                    new ForceFailPaymentCommand(transactionId: (string) $record->id),
                );

                Notification::make()
                    ->title('Транзакция помечена failed')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildSignedTinkoffPayload(PaymentTransaction $record, string $status): array
    {
        $config = app(TinkoffConfig::class);
        $signer = app(TinkoffTokenSigner::class);

        $payload = [
            'TerminalKey' => (string) $config->terminalKey(),
            'OrderId' => (string) $record->id,
            'Status' => $status,
            'Success' => $status === 'CONFIRMED',
            'Amount' => (int) $record->amount,
            'PaymentId' => $record->external_id ?? ('admin-refire-'.$record->id),
        ];

        $payload['Token'] = $signer->sign($payload, (string) $config->secretKey());

        return $payload;
    }
}
