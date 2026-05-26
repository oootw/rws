<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActionLogs;

use App\Filament\Resources\AdminActionLogs\Pages\ListAdminActionLogs;
use App\Filament\Resources\AdminActionLogs\Pages\ViewAdminActionLog;
use App\Filament\Resources\AdminActionLogs\Schemas\AdminActionLogInfolist;
use App\Filament\Resources\AdminActionLogs\Tables\AdminActionLogsTable;
use App\Models\AdminActionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Аудит-журнал admin-действий. Read-only — записи пишутся слушателем
 * LogAdminActionListener после каждого Filament Action::callAfter().
 */
final class AdminActionLogResource extends Resource
{
    protected static ?string $model = AdminActionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Аудит админа';

    protected static ?string $modelLabel = 'Запись';

    protected static ?string $pluralModelLabel = 'Аудит админа';

    protected static string|\UnitEnum|null $navigationGroup = 'Операционная';

    protected static ?int $navigationSort = 70;

    public static function infolist(Schema $schema): Schema
    {
        return AdminActionLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminActionLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminActionLogs::route('/'),
            'view' => ViewAdminActionLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
