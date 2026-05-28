<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActionLogs\Tables;

use App\Filament\Resources\ActionLogs\Schemas\ActionLogTypeLabels;
use App\Models\Place;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ActionLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('action_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ActionLogTypeLabels::for($state))
                    ->color(fn ($state): string => ActionLogTypeLabels::color($state))
                    ->sortable(),

                TextColumn::make('place.title')
                    ->label('Точка')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('place.user.name')
                    ->label('Владелец')
                    ->searchable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action_type')
                    ->label('Тип')
                    ->options(ActionLogTypeLabels::options()),

                SelectFilter::make('place_id')
                    ->label('Точка')
                    ->options(fn () => Place::query()
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable(),

                SelectFilter::make('owner_id')
                    ->label('Владелец')
                    ->options(fn () => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $q, array $data): Builder => empty($data['value'])
                        ? $q
                        : $q->whereHas('place', fn (Builder $sub) => $sub->where('user_id', $data['value']))),

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
}
