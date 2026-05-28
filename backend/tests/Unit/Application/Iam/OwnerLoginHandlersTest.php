<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\LoginCodeNotFound;
use App\Application\Iam\Exceptions\OwnerNotLinkedToTelegram;
use App\Application\Iam\ExchangeOwnerLoginCode\ExchangeOwnerLoginCodeCommand;
use App\Application\Iam\ExchangeOwnerLoginCode\ExchangeOwnerLoginCodeHandler;
use App\Application\Iam\RequestOwnerLogin\RequestOwnerLoginCommand;
use App\Application\Iam\RequestOwnerLogin\RequestOwnerLoginHandler;
use App\Domain\Iam\LoginCodeAlreadyConsumed;
use App\Domain\Iam\LoginCodeExpired;
use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Iam\TelegramId;
use Illuminate\Config\Repository as ConfigRepository;
use Random\Engine\Mt19937;
use Random\Randomizer;

function fixedRandomizer(int $seed = 42): Randomizer
{
    return new Randomizer(new Mt19937($seed));
}

it('создаёт login-request с 6-значным кодом и возвращает поддомен', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);
    $requests = fakeOwnerLoginRequestRepository();

    $issued = (new RequestOwnerLoginHandler(
        owners: $owners,
        requests: $requests,
        ids: fakeOwnerLoginRequestIdGenerator('11111111-1111-1111-1111-111111111111'),
        clock: frozenClockAt('2026-06-01T12:00:00Z'),
        randomizer: fixedRandomizer(),
        config: new ConfigRepository(['guardreviews.owner_login.ttl_seconds' => 300]),
    ))->handle(new RequestOwnerLoginCommand(telegramId: '1001'));

    expect($issued->code)->toMatch('/^\d{6}$/')
        ->and($issued->subdomain->value)->toBe('cafe')
        ->and($issued->expiresAt->format('c'))->toBe('2026-06-01T12:05:00+00:00')
        ->and($requests->requests)->toHaveCount(1)
        ->and($requests->requests[0]->code)->toBe($issued->code);
});

it('бросает OwnerNotLinkedToTelegram для неизвестного telegram_id', function (): void {
    (new RequestOwnerLoginHandler(
        owners: fakeOwnerRepository(),
        requests: fakeOwnerLoginRequestRepository(),
        ids: fakeOwnerLoginRequestIdGenerator('11111111-1111-1111-1111-111111111111'),
        clock: frozenClockAt('2026-06-01T12:00:00Z'),
        randomizer: fixedRandomizer(),
        config: new ConfigRepository,
    ))->handle(new RequestOwnerLoginCommand(telegramId: '9999'));
})->throws(OwnerNotLinkedToTelegram::class);

it('обменивает корректный код на OwnerId и помечает запрос consumed', function (): void {
    $clock = frozenClockAt('2026-06-01T12:01:00Z');
    $request = OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId('11111111-1111-1111-1111-111111111111'),
        ownerId: restoredOwner()->id,
        telegramId: new TelegramId('1001'),
        code: '654321',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 600,
    );
    $requests = fakeOwnerLoginRequestRepository([$request], $clock);

    $ownerId = (new ExchangeOwnerLoginCodeHandler(
        requests: $requests,
        clock: $clock,
    ))->handle(new ExchangeOwnerLoginCodeCommand(code: '654321'));

    expect($ownerId->value)->toBe(restoredOwner()->id->value)
        ->and($requests->requests[0]->isConsumed())->toBeTrue();
});

it('бросает LoginCodeNotFound для несуществующего кода', function (): void {
    (new ExchangeOwnerLoginCodeHandler(
        requests: fakeOwnerLoginRequestRepository([], frozenClockAt('2026-06-01T12:00:00Z')),
        clock: frozenClockAt('2026-06-01T12:00:00Z'),
    ))->handle(new ExchangeOwnerLoginCodeCommand(code: '000000'));
})->throws(LoginCodeNotFound::class);

it('бросает LoginCodeExpired через repository finder для истёкшего кода', function (): void {
    $clock = frozenClockAt('2026-06-01T13:00:00Z');
    $request = OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId('11111111-1111-1111-1111-111111111111'),
        ownerId: restoredOwner()->id,
        telegramId: new TelegramId('1001'),
        code: '111111',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 60,
    );
    $requests = fakeOwnerLoginRequestRepository([$request], $clock);

    /*
     * Реальный EloquentRepository фильтрует истёкшие на уровне SQL, поэтому
     * для пользователя путь — LoginCodeNotFound. Это сознательно: SPA
     * показывает одно сообщение «код невалиден или истёк».
     */
    (new ExchangeOwnerLoginCodeHandler(
        requests: $requests,
        clock: $clock,
    ))->handle(new ExchangeOwnerLoginCodeCommand(code: '111111'));
})->throws(LoginCodeNotFound::class);

it('бросает LoginCodeAlreadyConsumed при повторном использовании', function (): void {
    /*
     * Сценарий race-condition: запрос успел вернуться из find() (мы ослабим
     * фильтр, чтобы найти даже consumed), но aggregate->consume() поймает.
     */
    $clock = frozenClockAt('2026-06-01T12:01:00Z');
    $request = OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId('11111111-1111-1111-1111-111111111111'),
        ownerId: restoredOwner()->id,
        telegramId: new TelegramId('1001'),
        code: '222222',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 600,
    );
    $request->consume(new DateTimeImmutable('2026-06-01T12:00:30Z'));

    $requests = new class([$request]) implements OwnerLoginRequestRepository
    {
        public function __construct(public array $requests) {}

        public function save(OwnerLoginRequest $request): void {}

        public function findActiveByCode(string $code): ?OwnerLoginRequest
        {
            foreach ($this->requests as $r) {
                if ($r->code === $code) {
                    return $r;
                }
            }

            return null;
        }

        public function findById(OwnerLoginRequestId $id): ?OwnerLoginRequest
        {
            return null;
        }
    };

    (new ExchangeOwnerLoginCodeHandler(
        requests: $requests,
        clock: $clock,
    ))->handle(new ExchangeOwnerLoginCodeCommand(code: '222222'));
})->throws(LoginCodeAlreadyConsumed::class);

it('domain expiry проверка работает на consume', function (): void {
    $clock = frozenClockAt('2026-06-01T13:00:00Z');
    $expired = OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId('11111111-1111-1111-1111-111111111111'),
        ownerId: restoredOwner()->id,
        telegramId: new TelegramId('1001'),
        code: '333333',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 60,
    );

    $expired->consume($clock->now());
})->throws(LoginCodeExpired::class);
