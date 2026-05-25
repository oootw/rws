<?php

declare(strict_types=1);

namespace App\Application\Iam\UpdateOwnerProfile;

use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\Email;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TelegramId;

/**
 * Use case: админ (или будущий ЛК владельца) меняет профильные данные.
 *
 * Уникальность поддомена проверяется только если он действительно меняется —
 * иначе оставшийся неизменным slug всегда «существует» (сам у себя).
 */
final readonly class UpdateOwnerProfileHandler
{
    public function __construct(
        private OwnerRepository $owners,
    ) {}

    public function handle(UpdateOwnerProfileCommand $command): void
    {
        $owner = $this->owners->findById(new OwnerId($command->ownerId));

        if ($owner === null) {
            throw new TenantNotFound;
        }

        $newSubdomain = new SubdomainSlug($command->subdomain);

        if (! $owner->subdomainEquals($newSubdomain) && $this->owners->subdomainExists($newSubdomain)) {
            throw new SubdomainAlreadyTaken($newSubdomain->value);
        }

        $owner->changeProfile(
            name: $command->name,
            email: new Email($command->email),
            subdomain: $newSubdomain,
            telegramId: $command->telegramId !== null && $command->telegramId !== ''
                ? new TelegramId($command->telegramId)
                : null,
            tariffId: $command->tariffId !== null && $command->tariffId !== ''
                ? new TariffId($command->tariffId)
                : null,
        );

        $this->owners->save($owner);
    }
}
