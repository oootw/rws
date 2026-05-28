<?php

declare(strict_types=1);

namespace App\Application\Iam\IssueTelegramChatLinkToken;

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Domain\Iam\OwnerChatLinkToken;
use App\Domain\Iam\OwnerChatLinkTokenIdGenerator;
use App\Domain\Iam\OwnerChatLinkTokenRepository;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Domain\Shared\Clock\Clock;
use Illuminate\Contracts\Config\Repository as Config;
use Random\Randomizer;
use RuntimeException;

/**
 * Use case: Owner-панель просит one-shot токен для привязки группового
 * Telegram-чата. Handler сохраняет токен в БД и возвращает готовый deep-link
 * `https://t.me/{bot_username}?startgroup=<token>`.
 *
 * Token = 32 hex символа (16 байт CSPRNG) — достаточно для невзламываемости
 * и при этом коротко для URL.
 */
final readonly class IssueTelegramChatLinkTokenHandler
{
    private const DEFAULT_TTL_SECONDS = 600;

    private const TOKEN_BYTES = 16;

    public function __construct(
        private OwnerRepository $owners,
        private OwnerChatLinkTokenRepository $tokens,
        private OwnerChatLinkTokenIdGenerator $ids,
        private Clock $clock,
        private Randomizer $randomizer,
        private Config $config,
    ) {}

    public function handle(IssueTelegramChatLinkTokenCommand $command): IssuedChatLinkToken
    {
        $ownerId = new OwnerId($command->ownerId);

        if ($this->owners->findById($ownerId) === null) {
            throw new TenantNotFound;
        }

        $ttl = (int) $this->config->get('guardreviews.chat_link.ttl_seconds', self::DEFAULT_TTL_SECONDS);

        $token = OwnerChatLinkToken::issue(
            id: $this->ids->next(),
            ownerId: $ownerId,
            token: bin2hex($this->randomizer->getBytes(self::TOKEN_BYTES)),
            now: $this->clock->now(),
            ttlSeconds: $ttl,
        );

        $this->tokens->save($token);

        return new IssuedChatLinkToken(
            deepLink: $this->buildDeepLink($token->token),
            expiresAt: $token->expiresAt,
        );
    }

    private function buildDeepLink(string $token): string
    {
        $botUsername = (string) $this->config->get('guardreviews.telegram.bot_username', '');

        if ($botUsername === '') {
            throw new RuntimeException(
                'guardreviews.telegram.bot_username не настроен (env TELEGRAM_BOT_USERNAME).'
            );
        }

        return "https://t.me/{$botUsername}?startgroup={$token}";
    }
}
