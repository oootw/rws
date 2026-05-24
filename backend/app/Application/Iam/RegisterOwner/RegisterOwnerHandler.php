<?php

declare(strict_types=1);

namespace App\Application\Iam\RegisterOwner;

use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerIdGenerator;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\TariffRepository;
use App\Domain\Iam\TelegramId;

/**
 * Use case: новый владелец зарегистрировался (через Telegram-онбординг сегодня,
 * через веб завтра). Привязывает тариф по умолчанию, проверяет уникальность поддомена.
 */
final readonly class RegisterOwnerHandler
{
    public function __construct(
        private OwnerRepository $owners,
        private OwnerIdGenerator $idGenerator,
        private TariffRepository $tariffs,
    ) {}

    public function handle(RegisterOwnerCommand $command): OwnerId
    {
        $subdomain = new SubdomainSlug($command->subdomain);

        if ($this->owners->subdomainExists($subdomain)) {
            throw new SubdomainAlreadyTaken($subdomain->value);
        }

        $owner = Owner::register(
            id: $this->idGenerator->next(),
            name: $command->name,
            email: new Email($command->email),
            subdomain: $subdomain,
            telegramId: $command->telegramId !== null ? new TelegramId($command->telegramId) : null,
            tariffId: $this->tariffs->findDefault()?->id,
        );

        $this->owners->save($owner);

        return $owner->id;
    }
}
