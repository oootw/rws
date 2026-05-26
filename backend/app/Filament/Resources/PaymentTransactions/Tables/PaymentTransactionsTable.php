<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions\Tables;

use App\Domain\Payments\PaymentStatus;
use App\Filament\Resources\PaymentTransactions\Actions\PaymentTransactionActionFactory;
use App\Filament\Resources\PaymentTransactions\Schemas\PaymentTransactionInfolist;
use App\Models\Tariff;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class PaymentTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Владелец')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('tariff.title')
                    ->label('Тариф')
                    ->placeholder('—'),

                TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' ₽')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => PaymentTransactionInfolist::statusLabels()[$state->value])
                    ->color(fn ($state): string => match ($state) {
                        PaymentStatus::Pending => 'warning',
                        PaymentStatus::Success => 'success',
                        PaymentStatus::Failed => 'danger',
                        PaymentStatus::Refunded => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('external_id')
                    ->label('ID эквайера')
                    ->copyable()
                    ->limit(20)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(PaymentTransactionInfolist::statusLabels()),

                SelectFilter::make('user_id')
                    ->label('Владелец')
                    ->options(fn () => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('tariff_id')
                    ->label('Тариф')
                    ->options(fn () => Tariff::query()
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    PaymentTransactionActionFactory::refireWebhook(),
                    PaymentTransactionActionFactory::markFailed(),
                ])
                    ->label('Управление')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),
            ])
            ->toolbarActions([]);
    }
}
