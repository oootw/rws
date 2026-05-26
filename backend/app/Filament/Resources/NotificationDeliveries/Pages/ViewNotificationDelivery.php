<?php

declare(strict_types=1);

namespace App\Filament\Resources\NotificationDeliveries\Pages;

use App\Filament\Resources\NotificationDeliveries\NotificationDeliveryResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewNotificationDelivery extends ViewRecord
{
    protected static string $resource = NotificationDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
