<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActionLogs\Pages;

use App\Filament\Resources\AdminActionLogs\AdminActionLogResource;
use Filament\Resources\Pages\ListRecords;

final class ListAdminActionLogs extends ListRecords
{
    protected static string $resource = AdminActionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
