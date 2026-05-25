<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners\Tables;

use App\Application\Iam\DeleteOwner\DeleteOwnerCommand;
use App\Application\Iam\DeleteOwner\DeleteOwnerHandler;
use App\Filament\Resources\Owners\Actions\OwnerActionFactory;
use App\Models\Tariff;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Список владельцев с фильтрами и быстрыми действиями.
 *
 * Фильтр "подписка" — TernaryFilter на (subscription_ends_at > now()):
 *  - active = подписка ещё действует,
 *  - expired = истекла или никогда не оформлялась.
 */
final class OwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->limit(40),

                TextColumn::make('subdomain_slug')
                    ->label('Поддомен')
                    ->searchable()
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || $state === '') {
                            return '—';
                        }

                        return "{$state}.".config('guardreviews.domain', 'otziv.space');
                    })
                    ->copyable(),

                TextColumn::make('tariff.title')
                    ->label('Тариф')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('subscription_ends_at')
                    ->label('Подписка до')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state !== null && $state->isFuture() ? 'success' : 'danger')
                    ->placeholder('не оформлена'),

                TextColumn::make('telegram_id')
                    ->label('Telegram')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tariff_id')
                    ->label('Тариф')
                    ->options(fn () => Tariff::query()->pluck('title', 'id')->all()),

                TernaryFilter::make('subscription_status')
                    ->label('Подписка')
                    ->placeholder('Все')
                    ->trueLabel('Активная')
                    ->falseLabel('Истекла / не оформлена')
                    ->queries(
                        true: fn (Builder $q) => $q->where('subscription_ends_at', '>', now()),
                        false: fn (Builder $q) => $q->whereNull('subscription_ends_at')
                            ->orWhere('subscription_ends_at', '<=', now()),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    OwnerActionFactory::extendSubscription(),
                    OwnerActionFactory::overrideSubscription(),
                    OwnerActionFactory::changeTariff(),
                    OwnerActionFactory::impersonate(),
                ])
                    ->label('Управление')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),

                DeleteAction::make()
                    ->label('Удалить')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить владельца?')
                    ->modalDescription('Каскадно удалятся точки, отзывы, платежи и события. Действие необратимо.')
                    ->action(fn (User $record) => app(DeleteOwnerHandler::class)->handle(
                        new DeleteOwnerCommand(ownerId: (string) $record->id),
                    )),
            ])
            ->toolbarActions([]);
    }
}
