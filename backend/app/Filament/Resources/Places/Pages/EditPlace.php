<?php

declare(strict_types=1);

namespace App\Filament\Resources\Places\Pages;

use App\Application\Places\DeletePlace\DeletePlaceCommand;
use App\Application\Places\DeletePlace\DeletePlaceHandler;
use App\Application\Places\UpdatePlace\UpdatePlaceCommand;
use App\Application\Places\UpdatePlace\UpdatePlaceHandler;
use App\Filament\Resources\Places\PlaceResource;
use App\Filament\Resources\Places\Schemas\PlaceForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Редактирование точки. Сохранение проксируется в UpdatePlaceHandler.
 * Заголовок и удаление — через use cases, ровно как у OwnerResource.
 */
final class EditPlace extends EditRecord
{
    protected static string $resource = PlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить точку?')
                ->action(function (Model $record): void {
                    app(DeletePlaceHandler::class)->handle(
                        new DeletePlaceCommand(placeId: (string) $record->id),
                    );
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(UpdatePlaceHandler::class)->handle(new UpdatePlaceCommand(
            placeId: (string) $record->id,
            title: (string) $data['title'],
            platforms: PlaceForm::normalizeRepeaterPlatforms($data['platforms'] ?? []),
            backgroundImageUrl: ! empty($data['background_image_url']) ? (string) $data['background_image_url'] : null,
        ));

        return $record->refresh();
    }
}
