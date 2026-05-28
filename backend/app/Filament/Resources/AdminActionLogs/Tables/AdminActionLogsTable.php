<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActionLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AdminActionLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('admin_email')
                    ->label('Админ')
                    ->searchable(),

                TextColumn::make('action')
                    ->label('Действие')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('resource')
                    ->label('Ресурс')
                    ->formatStateUsing(fn (?string $state): string => self::shortClass($state))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('record_id')
                    ->label('Запись')
                    ->limit(12)
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('action')
                    ->label('Действие')
                    ->schema([
                        TextInput::make('value')->label('содержит'),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => empty($data['value'])
                        ? $q
                        : $q->where('action', 'like', '%'.$data['value'].'%')),

                Filter::make('admin_email')
                    ->label('Email админа')
                    ->schema([
                        TextInput::make('value')->label('содержит'),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => empty($data['value'])
                        ? $q
                        : $q->where('admin_email', 'like', '%'.$data['value'].'%')),

                Filter::make('created_at')
                    ->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С'),
                        DatePicker::make('until')->label('По'),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => $q
                        ->when($data['from'] ?? null, fn (Builder $sub, string $from) => $sub->whereDate('created_at', '>=', $from))
                        ->when($data['until'] ?? null, fn (Builder $sub, string $until) => $sub->whereDate('created_at', '<=', $until))),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function shortClass(?string $fqcn): string
    {
        if ($fqcn === null || $fqcn === '') {
            return '—';
        }

        $parts = explode('\\', $fqcn);

        return (string) end($parts);
    }
}
