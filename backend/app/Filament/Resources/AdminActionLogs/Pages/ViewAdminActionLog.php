<?php

declare(strict_types=1);

namespace App\Filament\Resources\AdminActionLogs\Pages;

use App\Filament\Resources\AdminActionLogs\AdminActionLogResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewAdminActionLog extends ViewRecord
{
    protected static string $resource = AdminActionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
