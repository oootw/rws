<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Dashboard-виджет: последние 10 отзывов с быстрым переходом в карточку.
 * Использует Filament TableWidget (это компактный список без фильтров).
 */
final class RecentReviewsWidget extends BaseWidget
{
    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Последние отзывы';

    public function table(Table $table): Table
    {
        return $table
            ->query(Review::query()->with('place.user')->latest())
            ->paginated([10])
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('created_at')->label('Когда')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('stars')
                    ->label('★')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state))
                    ->color(fn (int $state): string => $state <= 2 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),
                TextColumn::make('text')->label('Текст')->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('place.title')->label('Точка')->placeholder('—'),
                TextColumn::make('place.user.name')->label('Владелец')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state, Review $record): string => ReviewForm::statusOptions()[($state ?? $record->status)->value]),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Review $record): string => ReviewResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
