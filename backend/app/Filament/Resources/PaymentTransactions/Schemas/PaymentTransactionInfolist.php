<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions\Schemas;

use App\Domain\Payments\PaymentStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PaymentTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Платёж')
                ->columns(2)
                ->schema([
                    TextEntry::make('amount')
                        ->label('Сумма')
                        ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' ₽'),

                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn ($state): string => self::statusLabels()[$state->value])
                        ->color(fn ($state): string => match ($state) {
                            PaymentStatus::Pending => 'warning',
                            PaymentStatus::Success => 'success',
                            PaymentStatus::Failed => 'danger',
                            PaymentStatus::Refunded => 'gray',
                        }),

                    TextEntry::make('external_id')
                        ->label('ID эквайера')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('id')
                        ->label('ID транзакции')
                        ->copyable(),
                ]),

            Section::make('Владелец и тариф')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')->label('Владелец')->placeholder('—'),
                    TextEntry::make('user.email')->label('Email владельца')->copyable()->placeholder('—'),
                    TextEntry::make('tariff.title')->label('Тариф')->placeholder('—'),
                    TextEntry::make('user.subscription_ends_at')
                        ->label('Подписка до')
                        ->dateTime('d.m.Y H:i')
                        ->placeholder('не оформлена'),
                ]),

            Section::make('Системное')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                    TextEntry::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i'),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            PaymentStatus::Pending->value => 'Ожидает',
            PaymentStatus::Success->value => 'Успешно',
            PaymentStatus::Failed->value => 'Провален',
            PaymentStatus::Refunded->value => 'Возврат',
        ];
    }
}
