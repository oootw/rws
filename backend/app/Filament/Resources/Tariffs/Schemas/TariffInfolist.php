<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs\Schemas;

use App\Domain\Iam\Feature;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class TariffInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('Название'),
                    IconEntry::make('is_active')->label('Активен')->boolean(),
                    IconEntry::make('is_default')->label('По умолчанию')->boolean(),
                    TextEntry::make('price')
                        ->label('Цена')
                        ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' ₽'),
                    TextEntry::make('extra_place_price')
                        ->label('Доплата за точку')
                        ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2, ',', ' ').' ₽'),
                    TextEntry::make('duration_days')->label('Длительность, дней'),
                    TextEntry::make('places_limit')->label('Лимит точек'),
                ]),

            Section::make('Возможности тарифа')
                ->schema([
                    TextEntry::make('features')
                        ->label('')
                        ->hiddenLabel()
                        ->placeholder('—')
                        ->listWithLineBreaks()
                        ->formatStateUsing(static fn (?string $state): string => $state === null
                            ? '—'
                            : (Feature::tryFrom($state)?->label() ?? $state)),
                ]),

            Section::make('Системное')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('id')->label('ID')->copyable(),
                    TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                    TextEntry::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i'),
                ]),
        ]);
    }
}
