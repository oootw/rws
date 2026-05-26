<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActionLogs\Pages;

use App\Filament\Resources\ActionLogs\ActionLogResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewActionLog extends ViewRecord
{
    protected static string $resource = ActionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
