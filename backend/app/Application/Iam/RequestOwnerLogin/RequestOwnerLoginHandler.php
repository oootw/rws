<?php

declare(strict_types=1);

namespace App\Application\Iam\RequestOwnerLogin;

use App\Application\Iam\Exceptions\OwnerNotLinkedToTelegram;
use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestIdGenerator;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Iam\TelegramId;
use App\Domain\Shared\Clock\Clock;
use Illuminate\Contracts\Config\Repository as Config;
use Random\Randomizer;

/**
 * Use case: бот по команде /login выдаёт владельцу одноразовый 6-значный код.
 * Код передаётся в SPA через POST /api/owner/auth/exchange.
 */
final readonly class RequestOwnerLoginHandler
{
    private const DEFAULT_TTL_SECONDS = 600;

    public function __construct(
        private OwnerRepository $owners,
        private OwnerLoginRequestRepository $requests,
        private OwnerLoginRequestIdGenerator $ids,
        private Clock $clock,
        private Randomizer $randomizer,
        private Config $config,
    ) {}

    public function handle(RequestOwnerLoginCommand $command): IssuedLoginCode
    {
        $telegramId = new TelegramId($command->telegramId);
        $owner = $this->owners->findByTelegramId($telegramId);

        if ($owner === null) {
            throw new OwnerNotLinkedToTelegram;
        }

        $ttl = (int) $this->config->get('guardreviews.owner_login.ttl_seconds', self::DEFAULT_TTL_SECONDS);

        $request = OwnerLoginRequest::issue(
            id: $this->ids->next(),
            ownerId: $owner->id,
            telegramId: $telegramId,
            code: $this->generateCode(),
            now: $this->clock->now(),
            ttlSeconds: $ttl,
        );

        $this->requests->save($request);

        return new IssuedLoginCode(
            code: $request->code,
            expiresAt: $request->expiresAt,
            subdomain: $owner->subdomain(),
        );
    }

    private function generateCode(): string
    {
        return str_pad((string) $this->randomizer->getInt(0, 999_999), 6, '0', STR_PAD_LEFT);
    }
}
