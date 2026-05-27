<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs\Schemas;

use App\Domain\Iam\Feature;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Форма create/edit тарифа.
 *
 * is_default намеренно нет: его меняет отдельный action через
 * SetDefaultTariffHandler — иначе можно случайно создать «два default'а»
 * простой галочкой.
 */
final class TariffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),

                    Toggle::make('is_active')
                        ->label('Доступен для покупки')
                        ->default(true),

                    TextInput::make('price')
                        ->label('Цена, коп.')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->helperText('Базовая цена за первую точку. В копейках.'),

                    TextInput::make('extra_place_price')
                        ->label('Доплата за точку, коп.')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(0)
                        ->helperText('Доплата за каждую N-ю точку сверх первой. В копейках.'),

                    TextInput::make('duration_days')
                        ->label('Длительность, дней')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->required()
                        ->default(30),

                    TextInput::make('places_limit')
                        ->label('Лимит точек')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->required()
                        ->default(1),
                ]),

            Section::make('Возможности тарифа')
                ->collapsible()
                ->schema([
                    CheckboxList::make('features')
                        ->label('Включённые фичи')
                        ->options(collect(Feature::cases())
                            ->mapWithKeys(static fn (Feature $f) => [$f->value => $f->label()])
                            ->all())
                        ->columns(2)
                        ->bulkToggleable()
                        ->helperText('Снимите/поставьте галки, чтобы поменять доступ.'),
                ]),
        ]);
    }
}
