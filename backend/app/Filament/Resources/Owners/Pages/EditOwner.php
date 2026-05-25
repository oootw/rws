<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners\Pages;

use App\Application\Iam\DeleteOwner\DeleteOwnerCommand;
use App\Application\Iam\DeleteOwner\DeleteOwnerHandler;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileCommand;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileHandler;
use App\Filament\Resources\Owners\OwnerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Редактирование владельца. Сохранение проксируется в UpdateOwnerProfileHandler.
 * DeleteAction в заголовке тоже идёт через use case (тот же, что в таблице).
 */
final class EditOwner extends EditRecord
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Удалить владельца?')
                ->action(function (Model $record): void {
                    app(DeleteOwnerHandler::class)->handle(
                        new DeleteOwnerCommand(ownerId: (string) $record->id),
                    );
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(UpdateOwnerProfileHandler::class)->handle(new UpdateOwnerProfileCommand(
            ownerId: (string) $record->id,
            name: (string) $data['name'],
            email: (string) $data['email'],
            subdomain: (string) $data['subdomain_slug'],
            telegramId: ! empty($data['telegram_id']) ? (string) $data['telegram_id'] : null,
            tariffId: ! empty($data['tariff_id']) ? (string) $data['tariff_id'] : null,
        ));

        return $record->refresh();
    }
}
