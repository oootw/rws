<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places;

use App\Filament\Resources\Places\Pages\CreatePlace;
use App\Filament\Resources\Places\Pages\EditPlace;
use App\Filament\Resources\Places\Pages\ListPlaces;
use App\Filament\Resources\Places\Pages\ViewPlace;
use App\Filament\Resources\Places\Schemas\PlaceForm;
use App\Filament\Resources\Places\Schemas\PlaceInfolist;
use App\Filament\Resources\Places\Tables\PlacesTable;
use App\Models\Place;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Interface-слой (Filament) для управления точками.
 *
 * Архитектурный контракт:
 *  - $model = Place::class — это persistence-модель из app/Models,
 *    используется только для чтения и резолва маршрутов;
 *  - все мутации (create/edit/delete + activation toggle) идут через
 *    Application use cases (RegisterPlace / UpdatePlace /
 *    ChangePlaceActivation / DeletePlace).
 */
final class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Точки';

    protected static ?string $modelLabel = 'Точка';

    protected static ?string $pluralModelLabel = 'Точки';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return PlaceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlaceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlacesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlaces::route('/'),
            'create' => CreatePlace::route('/create'),
            'view' => ViewPlace::route('/{record}'),
            'edit' => EditPlace::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'title';
    }
}
