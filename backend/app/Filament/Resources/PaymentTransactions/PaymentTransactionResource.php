<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions;

use App\Filament\Resources\PaymentTransactions\Pages\ListPaymentTransactions;
use App\Filament\Resources\PaymentTransactions\Pages\ViewPaymentTransaction;
use App\Filament\Resources\PaymentTransactions\Schemas\PaymentTransactionInfolist;
use App\Filament\Resources\PaymentTransactions\Tables\PaymentTransactionsTable;
use App\Models\PaymentTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Interface-слой (Filament) для платёжных транзакций.
 *
 * Ресурс — read-only: формы create/edit отсутствуют (транзакции создаются
 * use case'ом InitSubscriptionPayment и меняются только webhook'ом или
 * админскими actions: refire_webhook и mark_failed).
 */
final class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Платежи';

    protected static ?string $modelLabel = 'Платёж';

    protected static ?string $pluralModelLabel = 'Платежи';

    protected static ?int $navigationSort = 50;

    public static function infolist(Schema $schema): Schema
    {
        return PaymentTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTransactions::route('/'),
            'view' => ViewPaymentTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
