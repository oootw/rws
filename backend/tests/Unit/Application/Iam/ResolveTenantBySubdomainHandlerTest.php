<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\ResolveTenantBySubdomain\ResolveTenantBySubdomainHandler;
use App\Application\Iam\ResolveTenantBySubdomain\ResolveTenantBySubdomainQuery;
use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\TelegramId;

function fakeOwnersRepo(?Owner $stored = null): OwnerRepository
{
    return new class($stored) implements OwnerRepository
    {
        public function __construct(public ?Owner $stored) {}

        public function save(Owner $owner): void
        {
            $this->stored = $owner;
        }

        public function findById(OwnerId $id): ?Owner
        {
            return $this->stored !== null && $this->stored->id->value === $id->value ? $this->stored : null;
        }

        public function findBySubdomain(SubdomainSlug $subdomain): ?Owner
        {
            return $this->stored !== null && $this->stored->subdomain()->value === $subdomain->value
                ? $this->stored : null;
        }

        public function findByTelegramId(TelegramId $telegramId): ?Owner
        {
            return $this->stored !== null && $this->stored->telegramId()?->value === $telegramId->value
                ? $this->stored : null;
        }

        public function subdomainExists(SubdomainSlug $subdomain): bool
        {
            return $this->findBySubdomain($subdomain) !== null;
        }
    };
}

function freshOwner(string $subdomain = 'cafe'): Owner
{
    return Owner::register(
        id: new OwnerId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        name: 'Owner',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug($subdomain),
        telegramId: null,
        tariffId: null,
    );
}

it('возвращает владельца по поддомену', function (): void {
    $handler = new ResolveTenantBySubdomainHandler(fakeOwnersRepo(freshOwner('cafe')));

    $owner = $handler->handle(new ResolveTenantBySubdomainQuery(subdomain: 'cafe'));

    expect($owner->subdomain()->value)->toBe('cafe');
});

it('бросает TenantNotFound, если владельца нет', function (): void {
    (new ResolveTenantBySubdomainHandler(fakeOwnersRepo()))->handle(
        new ResolveTenantBySubdomainQuery(subdomain: 'missing'),
    );
})->throws(TenantNotFound::class);

it('бросает TenantNotFound на невалидном поддомене', function (): void {
    (new ResolveTenantBySubdomainHandler(fakeOwnersRepo(freshOwner())))->handle(
        new ResolveTenantBySubdomainQuery(subdomain: 'a'),
    );
})->throws(TenantNotFound::class);

it('бросает TenantNotFound на зарезервированном поддомене', function (): void {
    (new ResolveTenantBySubdomainHandler(fakeOwnersRepo()))->handle(
        new ResolveTenantBySubdomainQuery(subdomain: 'api'),
    );
})->throws(TenantNotFound::class);
