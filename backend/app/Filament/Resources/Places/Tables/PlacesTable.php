<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Tables;

use App\Application\Places\DeletePlace\DeletePlaceCommand;
use App\Application\Places\DeletePlace\DeletePlaceHandler;
use App\Filament\Resources\Places\Actions\PlaceActionFactory;
use App\Models\Place;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Список точек с фильтрами по владельцу и статусу + QR/активация
 * как inline actions.
 */
final class PlacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('user.name')
                    ->label('Владелец')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('platforms')
                    ->label('Площадок')
                    ->formatStateUsing(fn ($state): int => is_array($state) ? count($state) : 0)
                    ->alignCenter(),

                TextColumn::make('reviews_count')
                    ->label('Отзывов')
                    ->counts('reviews')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Владелец')
                    ->options(fn () => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Выключенные')
                    ->queries(
                        true: fn (Builder $q) => $q->where('is_active', true),
                        false: fn (Builder $q) => $q->where('is_active', false),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    PlaceActionFactory::toggleActivation(),
                    PlaceActionFactory::previewQr(),
                    PlaceActionFactory::downloadQr(),
                ])
                    ->label('Управление')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),

                DeleteAction::make()
                    ->label('Удалить')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить точку?')
                    ->modalDescription('Каскадно удалятся отзывы и события точки. Действие необратимо.')
                    ->action(fn (Place $record) => app(DeletePlaceHandler::class)->handle(
                        new DeletePlaceCommand(placeId: (string) $record->id),
                    )),
            ])
            ->toolbarActions([
                PlaceActionFactory::bulkActivate(),
                PlaceActionFactory::bulkDeactivate(),
            ]);
    }
}
