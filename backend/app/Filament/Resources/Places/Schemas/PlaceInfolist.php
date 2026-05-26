<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Карточка просмотра точки. Только чтение; для управления — actions
 * в таблице или EditAction в шапке.
 */
final class PlaceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('Название'),
                    TextEntry::make('user.name')
                        ->label('Владелец')
                        ->placeholder('—'),
                    TextEntry::make('is_active')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Активна' : 'Выключена'),
                    TextEntry::make('id')
                        ->label('ID точки')
                        ->copyable(),
                ]),

            Section::make('Фон и оформление')
                ->schema([
                    TextEntry::make('background_image_url')
                        ->label('Фоновое изображение')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->placeholder('не задано'),
                ]),

            Section::make('Площадки')
                ->schema([
                    RepeatableEntry::make('platforms')
                        ->label('')
                        ->hiddenLabel()
                        ->placeholder('Площадки не настроены')
                        ->schema([
                            TextEntry::make('label')->label('Подпись'),
                            TextEntry::make('type')->label('Тип')->badge(),
                            TextEntry::make('url')
                                ->label('Ссылка')
                                ->url(fn (?string $state): ?string => $state)
                                ->openUrlInNewTab(),
                        ])
                        ->columns(3),
                ]),

            Section::make('Системное')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')->label('Создана')->dateTime('d.m.Y H:i'),
                    TextEntry::make('updated_at')->label('Обновлена')->dateTime('d.m.Y H:i'),
                ]),
        ]);
    }
}
