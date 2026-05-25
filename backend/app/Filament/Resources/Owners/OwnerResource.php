<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners;

use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Filament\Resources\Owners\Pages\ViewOwner;
use App\Filament\Resources\Owners\Schemas\OwnerForm;
use App\Filament\Resources\Owners\Schemas\OwnerInfolist;
use App\Filament\Resources\Owners\Tables\OwnersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Interface-слой (Filament) для управления владельцами.
 *
 * Архитектурный контракт:
 *  - $model = User::class — это persistence-модель из app/Models,
 *    используется только для чтения и резолва маршрутов;
 *  - все мутации (create/edit/delete + custom actions) идут через
 *    Application use cases (RegisterOwner / UpdateOwnerProfile /
 *    DeleteOwner / OverrideSubscription / ChangeOwnerTariff /
 *    ExtendSubscription / IssueOwnerImpersonationToken).
 */
final class OwnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Владельцы';

    protected static ?string $modelLabel = 'Владелец';

    protected static ?string $pluralModelLabel = 'Владельцы';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return OwnerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OwnerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OwnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOwners::route('/'),
            'create' => CreateOwner::route('/create'),
            'view' => ViewOwner::route('/{record}'),
            'edit' => EditOwner::route('/{record}/edit'),
        ];
    }

    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }
}
