<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActionLogs;

use App\Filament\Resources\ActionLogs\Pages\ListActionLogs;
use App\Filament\Resources\ActionLogs\Pages\ViewActionLog;
use App\Filament\Resources\ActionLogs\Schemas\ActionLogInfolist;
use App\Filament\Resources\ActionLogs\Tables\ActionLogsTable;
use App\Models\ActionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Журнал действий посетителей: сканы QR, переходы на внешние площадки,
 * оставленные негативные отзывы и удаления отзывов админом.
 *
 * Read-only — записи пишутся use case'ами (RecordAction / DeleteReview);
 * вручную создавать или редактировать смысла нет.
 */
final class ActionLogResource extends Resource
{
    protected static ?string $model = ActionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Журнал действий';

    protected static ?string $modelLabel = 'Запись';

    protected static ?string $pluralModelLabel = 'Журнал действий';

    protected static string|\UnitEnum|null $navigationGroup = 'Операционная';

    protected static ?int $navigationSort = 60;

    public static function infolist(Schema $schema): Schema
    {
        return ActionLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActionLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActionLogs::route('/'),
            'view' => ViewActionLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
