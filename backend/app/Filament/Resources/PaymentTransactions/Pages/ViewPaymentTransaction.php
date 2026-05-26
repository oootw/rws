<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions\Pages;

use App\Filament\Resources\PaymentTransactions\Actions\PaymentTransactionActionFactory;
use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewPaymentTransaction extends ViewRecord
{
    protected static string $resource = PaymentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PaymentTransactionActionFactory::refireWebhook(),
            PaymentTransactionActionFactory::markFailed(),
        ];
    }
}
