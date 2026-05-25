<?php

declare(strict_types=1);

use App\Domain\Iam\Email;
use App\Domain\Iam\Owner;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerIdGenerator;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Iam\Subscription;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;
use App\Domain\Iam\TariffRepository;
use App\Domain\Iam\TelegramId;
use App\Domain\Shared\Clock\Clock;

function sampleOwner(
    string $id = '22222222-2222-2222-2222-222222222222',
    string $subdomain = 'cafe',
): Owner {
    return Owner::register(
        id: new OwnerId($id),
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug($subdomain),
        telegramId: new TelegramId('1001'),
        tariffId: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
    );
}

function restoredOwner(string $id = '22222222-2222-2222-2222-222222222222'): Owner
{
    return Owner::restore(
        id: new OwnerId($id),
        name: 'Иван',
        email: new Email('owner@example.com'),
        subdomain: new SubdomainSlug('cafe'),
        telegramId: new TelegramId('1001'),
        maxId: null,
        tariffId: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        subscription: Subscription::none(),
    );
}

/**
 * @param  list<Owner>  $owners
 * @param  list<string>  $takenSubdomains
 */
function fakeOwnerRepository(array $owners = [], array $takenSubdomains = []): OwnerRepository
{
    return new class($owners, $takenSubdomains) implements OwnerRepository
    {
        /** @var list<Owner> */
        public array $owners;

        /** @param  list<Owner>  $owners */
        /** @param  list<string>  $takenSubdomains */
        public function __construct(array $owners, private array $takenSubdomains)
        {
            $this->owners = $owners;
        }

        public function save(Owner $owner): void
        {
            foreach ($this->owners as $index => $stored) {
                if ($stored->id->equals($owner->id)) {
                    $this->owners[$index] = $owner;

                    return;
                }
            }

            $this->owners[] = $owner;
        }

        public function findById(OwnerId $id): ?Owner
        {
            foreach ($this->owners as $owner) {
                if ($owner->id->equals($id)) {
                    return $owner;
                }
            }

            return null;
        }

        public function findBySubdomain(SubdomainSlug $subdomain): ?Owner
        {
            foreach ($this->owners as $owner) {
                if ($owner->subdomain()->value === $subdomain->value) {
                    return $owner;
                }
            }

            return null;
        }

        public function findByTelegramId(TelegramId $telegramId): ?Owner
        {
            foreach ($this->owners as $owner) {
                if ($owner->telegramId()?->value === $telegramId->value) {
                    return $owner;
                }
            }

            return null;
        }

        public function subdomainExists(SubdomainSlug $subdomain): bool
        {
            if (in_array($subdomain->value, $this->takenSubdomains, true)) {
                return true;
            }

            return $this->findBySubdomain($subdomain) !== null;
        }

        public function delete(OwnerId $id): void
        {
            $this->owners = array_values(array_filter(
                $this->owners,
                static fn (Owner $owner) => ! $owner->id->equals($id),
            ));
        }
    };
}

function fakeOwnerIdGenerator(string $value): OwnerIdGenerator
{
    return new class($value) implements OwnerIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): OwnerId
        {
            return new OwnerId($this->value);
        }
    };
}

/**
 * @param  list<Tariff>  $tariffs
 */
function fakeTariffRepository(?Tariff $default = null, array $tariffs = []): TariffRepository
{
    return new class($default, $tariffs) implements TariffRepository
    {
        /** @param  list<Tariff>  $tariffs */
        public function __construct(private ?Tariff $default, private array $tariffs) {}

        public function findById(TariffId $id): ?Tariff
        {
            foreach ($this->tariffs as $tariff) {
                if ($tariff->id->value === $id->value) {
                    return $tariff;
                }
            }

            return null;
        }

        public function findDefault(): ?Tariff
        {
            return $this->default;
        }
    };
}

function frozenClockAt(string $at): Clock
{
    return new class($at) implements Clock
    {
        public function __construct(private string $at) {}

        public function now(): DateTimeImmutable
        {
            return new DateTimeImmutable($this->at);
        }
    };
}

function defaultTariff(): Tariff
{
    return new Tariff(
        id: new TariffId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        title: 'MVP',
    );
}
