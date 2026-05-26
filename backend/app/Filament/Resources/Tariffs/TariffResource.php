<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tariffs;

use App\Filament\Resources\Tariffs\Pages\CreateTariff;
use App\Filament\Resources\Tariffs\Pages\EditTariff;
use App\Filament\Resources\Tariffs\Pages\ListTariffs;
use App\Filament\Resources\Tariffs\Pages\ViewTariff;
use App\Filament\Resources\Tariffs\Schemas\TariffForm;
use App\Filament\Resources\Tariffs\Schemas\TariffInfolist;
use App\Filament\Resources\Tariffs\Tables\TariffsTable;
use App\Models\Tariff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Interface-слой (Filament) для тарифов — конфигурационная сущность
 * без сложных инвариантов. CRUD идёт напрямую через Eloquent (без use case'ов
 * на каждую правку); единственное исключение — назначение default,
 * проходит через SetDefaultTariffHandler (инвариант «ровно один is_default»).
 */
final class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Тарифы';

    protected static ?string $modelLabel = 'Тариф';

    protected static ?string $pluralModelLabel = 'Тарифы';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return TariffForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TariffInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TariffsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTariffs::route('/'),
            'create' => CreateTariff::route('/create'),
            'view' => ViewTariff::route('/{record}'),
            'edit' => EditTariff::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'title';
    }
}
