<?php

declare(strict_types=1);

namespace App\Filament\Resources\Owners\Pages;

use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffCommand;
use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffHandler;
use App\Application\Iam\RegisterOwner\RegisterOwnerCommand;
use App\Application\Iam\RegisterOwner\RegisterOwnerHandler;
use App\Filament\Resources\Owners\OwnerResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Создание владельца через RegisterOwnerHandler.
 * Form-данные превращаются в Command, Eloquent-сохранение не запускается —
 * use case сам кладёт запись через репозиторий.
 */
final class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $handler = app(RegisterOwnerHandler::class);

        $ownerId = $handler->handle(new RegisterOwnerCommand(
            name: (string) $data['name'],
            email: (string) $data['email'],
            subdomain: (string) $data['subdomain_slug'],
            telegramId: ! empty($data['telegram_id']) ? (string) $data['telegram_id'] : null,
        ));

        $created = User::query()->findOrFail($ownerId->value);

        // Тариф приходит отдельной мутацией — RegisterOwnerCommand сегодня
        // тариф не принимает (он подбирается как default). Если админ выбрал
        // другой при создании — применим сразу через UpdateOwnerProfile.
        if (! empty($data['tariff_id']) && (string) $data['tariff_id'] !== (string) $created->tariff_id) {
            app(ChangeOwnerTariffHandler::class)->handle(
                new ChangeOwnerTariffCommand(
                    ownerId: $ownerId->value,
                    tariffId: (string) $data['tariff_id'],
                ),
            );
            $created->refresh();
        }

        return $created;
    }
}
