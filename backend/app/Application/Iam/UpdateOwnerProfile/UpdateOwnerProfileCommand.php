<?php

declare(strict_types=1);

namespace App\Application\Iam\UpdateOwnerProfile;

/**
 * Иммутабельный ввод для use case "обновить профиль владельца".
 * Все поля обязательны (это полное обновление, не частичный patch);
 * вызывающий ответственен за то, чтобы передать актуальные значения.
 */
final readonly class UpdateOwnerProfileCommand
{
    public function __construct(
        public string $ownerId,
        public string $name,
        public string $email,
        public string $subdomain,
        public ?string $telegramId,
        public ?string $tariffId,
    ) {}
}
