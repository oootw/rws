<?php

declare(strict_types=1);

namespace App\Domain\Iam;

interface OwnerChatLinkTokenRepository
{
    public function save(OwnerChatLinkToken $token): void;

    /**
     * Возвращает токен только если он ещё не использован и не истёк
     * (фильтрация по expires_at — в БД, чтобы не возить мусор).
     */
    public function findActiveByToken(string $token): ?OwnerChatLinkToken;

    public function findById(OwnerChatLinkTokenId $id): ?OwnerChatLinkToken;
}
