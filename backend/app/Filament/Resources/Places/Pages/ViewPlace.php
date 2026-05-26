<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Pages;

use App\Filament\Resources\Places\Actions\PlaceActionFactory;
use App\Filament\Resources\Places\PlaceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPlace extends ViewRecord
{
    protected static string $resource = PlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            PlaceActionFactory::previewQr(),
            PlaceActionFactory::downloadQr(),
            PlaceActionFactory::toggleActivation(),
        ];
    }
}
