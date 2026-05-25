<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Карточка просмотра владельца. Только чтение — все мутации через actions.
 */
final class OwnerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Профиль')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Имя'),
                    TextEntry::make('email')->label('Email')->copyable(),
                    TextEntry::make('subdomain_slug')
                        ->label('Поддомен')
                        ->formatStateUsing(fn (?string $state): string => $state === null
                            ? '—'
                            : "{$state}.".config('guardreviews.domain', 'otziv.space'))
                        ->copyable(),
                    TextEntry::make('tariff.title')
                        ->label('Тариф')
                        ->badge()
                        ->placeholder('—'),
                ]),

            Section::make('Подписка')
                ->columns(2)
                ->schema([
                    TextEntry::make('subscription_ends_at')
                        ->label('Действует до')
                        ->dateTime('d.m.Y H:i')
                        ->badge()
                        ->color(fn ($state) => $state !== null && $state->isFuture() ? 'success' : 'danger')
                        ->placeholder('не оформлена'),
                    TextEntry::make('id')
                        ->label('ID владельца')
                        ->copyable(),
                ]),

            Section::make('Контакты')
                ->columns(2)
                ->schema([
                    TextEntry::make('telegram_id')->label('Telegram ID')->placeholder('—'),
                    TextEntry::make('max_id')->label('MAX ID')->placeholder('—'),
                ]),

            Section::make('Системное')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')->label('Зарегистрирован')->dateTime('d.m.Y H:i'),
                    TextEntry::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i'),
                ]),
        ]);
    }
}
