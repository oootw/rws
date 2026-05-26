<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationDeliveries;

use App\Application\Notifications\Logging\NotificationDeliveryStatus;
use App\Filament\Resources\NotificationDeliveries\Pages\ListNotificationDeliveries;
use App\Filament\Resources\NotificationDeliveries\Pages\ViewNotificationDelivery;
use App\Models\NotificationDelivery;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Журнал доставки уведомлений (Telegram/Max/Email). Пишется из
 * MultiChannelOwnerNotifier по каждой попытке. Read-only.
 *
 * Все составляющие (infolist/table) собраны прямо здесь —
 * ресурс простой и без custom actions, отдельные Schema/Tables-классы
 * только раздули бы код.
 */
final class NotificationDeliveryResource extends Resource
{
    protected static ?string $model = NotificationDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Журнал уведомлений';

    protected static ?string $modelLabel = 'Доставка';

    protected static ?string $pluralModelLabel = 'Журнал уведомлений';

    protected static string|\UnitEnum|null $navigationGroup = 'Операционная';

    protected static ?int $navigationSort = 70;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Доставка')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')->label('Когда')->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('channel')->label('Канал')->badge(),
                    TextEntry::make('kind')->label('Тип уведомления')->badge(),
                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn ($state, NotificationDelivery $record): string => self::statusLabel($state ?? $record->status))
                        ->color(fn ($state, NotificationDelivery $record): string => self::statusColor($state ?? $record->status)),
                ]),
            Section::make('Владелец')
                ->columns(2)
                ->schema([
                    TextEntry::make('owner.name')->label('Имя')->placeholder('—'),
                    TextEntry::make('owner.email')->label('Email')->copyable()->placeholder('—'),
                    TextEntry::make('owner_id')->label('ID владельца')->copyable()->placeholder('—'),
                ]),
            Section::make('Ошибка')
                ->visible(fn (NotificationDelivery $record): bool => $record->error !== null)
                ->schema([
                    TextEntry::make('error')
                        ->label('')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->copyable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Когда')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('owner.name')->label('Владелец')->searchable()->placeholder('—'),
                TextColumn::make('channel')->label('Канал')->badge()->sortable(),
                TextColumn::make('kind')->label('Тип')->badge()->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state, NotificationDelivery $record): string => self::statusLabel($state ?? $record->status))
                    ->color(fn ($state, NotificationDelivery $record): string => self::statusColor($state ?? $record->status))
                    ->sortable(),
                TextColumn::make('error')
                    ->label('Ошибка')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(NotificationDeliveryStatus::cases())
                        ->mapWithKeys(fn (NotificationDeliveryStatus $s): array => [$s->value => self::statusLabel($s)])
                        ->all()),

                SelectFilter::make('channel')
                    ->label('Канал')
                    ->options(['Telegram' => 'Telegram', 'Max' => 'Max', 'Email' => 'Email']),

                SelectFilter::make('owner_id')
                    ->label('Владелец')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationDeliveries::route('/'),
            'view' => ViewNotificationDelivery::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function statusLabel(?NotificationDeliveryStatus $status): string
    {
        if ($status === null) {
            return '—';
        }

        return match ($status) {
            NotificationDeliveryStatus::Delivered => 'Доставлено',
            NotificationDeliveryStatus::Failed => 'Ошибка',
            NotificationDeliveryStatus::Skipped => 'Пропущено',
        };
    }

    private static function statusColor(?NotificationDeliveryStatus $status): string
    {
        if ($status === null) {
            return 'gray';
        }

        return match ($status) {
            NotificationDeliveryStatus::Delivered => 'success',
            NotificationDeliveryStatus::Failed => 'danger',
            NotificationDeliveryStatus::Skipped => 'gray',
        };
    }
}
