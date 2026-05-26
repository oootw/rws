<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs\Tables;

use App\Filament\Resources\Tariffs\Actions\TariffActionFactory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class TariffsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' ₽')
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Дней')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('places_limit')
                    ->label('Точек')
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                TextColumn::make('users_count')
                    ->label('Пользователей')
                    ->counts('users')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Активные')
                    ->placeholder('Все'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                TariffActionFactory::setDefault(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Удалить тариф?')
                    ->modalDescription('Удаление возможно, только если у тарифа нет пользователей и платежей (FK ON DELETE RESTRICT).'),
            ])
            ->toolbarActions([]);
    }
}
