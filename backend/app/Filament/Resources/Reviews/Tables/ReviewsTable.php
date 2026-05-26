<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Tables;

use App\Application\Reviews\DeleteReview\DeleteReviewCommand;
use App\Application\Reviews\DeleteReview\DeleteReviewHandler;
use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\Actions\ReviewActionFactory;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Список отзывов с фильтрами модерации. Дефолтная сортировка — свежие
 * сверху, как и хочет модератор.
 */
final class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('stars')
                    ->label('Оценка')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color(fn (int $state): string => $state <= 2 ? 'danger' : ($state <= 3 ? 'warning' : 'success'))
                    ->sortable(),

                TextColumn::make('text')
                    ->label('Текст')
                    ->limit(60)
                    ->wrap()
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('contact')
                    ->label('Контакт')
                    ->copyable()
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('place.title')
                    ->label('Точка')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('place.user.name')
                    ->label('Владелец')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ReviewForm::statusOptions()[$state->value])
                    ->color(fn ($state): string => match ($state) {
                        ReviewStatus::New => 'danger',
                        ReviewStatus::InProgress => 'warning',
                        ReviewStatus::Resolved => 'success',
                        ReviewStatus::Archived => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Оставлен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ReviewForm::statusOptions()),

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
                    ->modifyQueryUsing(function (Builder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereHas('place', fn (Builder $sub) => $sub->where('user_id', $data['value']));
                    }),

                Filter::make('created_at')
                    ->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С'),
                        DatePicker::make('until')->label('По'),
                    ])
                    ->modifyQueryUsing(function (Builder $query, array $data): void {
                        $query
                            ->when($data['from'] ?? null, fn (Builder $sub, string $from) => $sub->whereDate('created_at', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $sub, string $until) => $sub->whereDate('created_at', '<=', $until));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    ReviewActionFactory::changeStatus(),
                    ReviewActionFactory::resendAlert(),
                ])
                    ->label('Управление')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),

                DeleteAction::make()
                    ->label('Удалить')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить отзыв?')
                    ->modalDescription('Используйте, если отзыв — спам. В журнал действий запишется AdminDeletedReview.')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Причина (необязательно)')
                            ->maxLength(500),
                    ])
                    ->action(fn (Review $record, array $data) => app(DeleteReviewHandler::class)->handle(
                        new DeleteReviewCommand(
                            reviewId: (string) $record->id,
                            reason: ! empty($data['reason']) ? (string) $data['reason'] : null,
                        ),
                    )),
            ])
            ->toolbarActions([]);
    }
}
