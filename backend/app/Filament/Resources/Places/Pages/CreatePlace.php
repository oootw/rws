<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Pages;

use App\Application\Places\RegisterPlace\RegisterPlaceCommand;
use App\Application\Places\RegisterPlace\RegisterPlaceHandler;
use App\Filament\Resources\Places\PlaceResource;
use App\Filament\Resources\Places\Schemas\PlaceForm;
use App\Models\Place;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Создание точки через RegisterPlaceHandler. Eloquent::save() не запускается —
 * use case кладёт запись через репозиторий.
 */
final class CreatePlace extends CreateRecord
{
    protected static string $resource = PlaceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $placeId = app(RegisterPlaceHandler::class)->handle(new RegisterPlaceCommand(
            ownerId: (string) $data['user_id'],
            title: (string) $data['title'],
            platforms: PlaceForm::normalizeRepeaterPlatforms($data['platforms'] ?? []),
            backgroundImageUrl: ! empty($data['background_image_url']) ? (string) $data['background_image_url'] : null,
        ));

        return Place::query()->findOrFail($placeId->value);
    }
}
