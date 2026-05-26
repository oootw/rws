<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Карточка отзыва: всё содержимое для разбора жалобы и принятия решения.
 */
final class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Отзыв')
                ->columns(2)
                ->schema([
                    TextEntry::make('stars')
                        ->label('Оценка')
                        ->badge()
                        ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                        ->color(fn (int $state): string => $state <= 2 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),

                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn ($state): string => ReviewForm::statusOptions()[$state->value])
                        ->color(fn ($state): string => match ($state) {
                            ReviewStatus::New => 'danger',
                            ReviewStatus::InProgress => 'warning',
                            ReviewStatus::Resolved => 'success',
                            ReviewStatus::Archived => 'gray',
                        }),

                    TextEntry::make('contact')
                        ->label('Контакт автора')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label('Оставлен')
                        ->dateTime('d.m.Y H:i'),

                    TextEntry::make('text')
                        ->label('Текст')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),

            Section::make('Точка и владелец')
                ->columns(2)
                ->schema([
                    TextEntry::make('place.title')->label('Точка')->placeholder('—'),
                    TextEntry::make('place.user.name')->label('Владелец')->placeholder('—'),
                    TextEntry::make('place.user.email')->label('Email владельца')->copyable()->placeholder('—'),
                ]),

            Section::make('Системное')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('id')->label('ID отзыва')->copyable(),
                    TextEntry::make('place_id')->label('ID точки')->copyable(),
                ]),
        ]);
    }
}
