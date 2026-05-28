<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\OwnerLoginRequestIdGenerator;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Iam\TelegramId;
use App\Domain\Shared\Clock\Clock;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @param  list<OwnerLoginRequest>  $requests
 */
function fakeOwnerLoginRequestRepository(array $requests = [], ?Clock $clock = null): OwnerLoginRequestRepository
{
    return new class($requests, $clock) implements OwnerLoginRequestRepository
    {
        /** @var list<OwnerLoginRequest> */
        public array $requests;

        public function __construct(array $requests, private ?Clock $clock)
        {
            $this->requests = $requests;
        }

        public function save(OwnerLoginRequest $request): void
        {
            foreach ($this->requests as $index => $stored) {
                if ($stored->id->equals($request->id)) {
                    $this->requests[$index] = $request;

                    return;
                }
            }

            $this->requests[] = $request;
        }

        public function findActiveByCode(string $code): ?OwnerLoginRequest
        {
            $now = $this->clock?->now();

            foreach ($this->requests as $request) {
                if ($request->code !== $code) {
                    continue;
                }
                if ($request->isConsumed()) {
                    continue;
                }
                if ($now !== null && $request->isExpiredAt($now)) {
                    continue;
                }

                return $request;
            }

            return null;
        }

        public function findById(OwnerLoginRequestId $id): ?OwnerLoginRequest
        {
            foreach ($this->requests as $request) {
                if ($request->id->equals($id)) {
                    return $request;
                }
            }

            return null;
        }
    };
}

function fakeOwnerLoginRequestIdGenerator(string $value): OwnerLoginRequestIdGenerator
{
    return new class($value) implements OwnerLoginRequestIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): OwnerLoginRequestId
        {
            return new OwnerLoginRequestId($this->value);
        }
    };
}

/**
 * Feature-test helper: создаёт активный login-request в БД.
 */
function issueLoginRequest(
    string $ownerId,
    string $code,
    string $telegramId = '1001',
    ?DateTimeImmutable $now = null,
    int $ttlSeconds = 600,
): void {
    $now ??= new DateTimeImmutable('now');
    $request = OwnerLoginRequest::issue(
        id: new OwnerLoginRequestId((string) Str::uuid()),
        ownerId: new OwnerId($ownerId),
        telegramId: new TelegramId($telegramId),
        code: $code,
        now: $now,
        ttlSeconds: $ttlSeconds,
    );
    app(OwnerLoginRequestRepository::class)->save($request);
}

/**
 * Feature-test helper: логинит owner-а через /auth/exchange (cookie-сессия).
 */
function loginAsOwner(User $user, string $code = '000000'): void
{
    issueLoginRequest($user->id, $code);
    test()->postJson('/api/owner/auth/exchange', ['code' => $code], tenantHeaders($user))->assertOk();
}
