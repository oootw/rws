<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Domain\Iam\Owner;

/**
 * DTO для ответов `/api/owner/me` и `/api/owner/auth/exchange`.
 * Гарантирует единый JSON-контракт сессии в обоих эндпоинтах.
 */
final readonly class OwnerMeView
{
    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     subdomain: string,
     *     telegram_connected: bool,
     * }
     */
    public static function fromOwner(Owner $owner): array
    {
        return [
            'id' => $owner->id->value,
            'name' => $owner->name(),
            'email' => $owner->email()->value,
            'subdomain' => $owner->subdomain()->value,
            'telegram_connected' => $owner->telegramId() !== null,
        ];
    }
}
