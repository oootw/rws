<?php

declare(strict_types=1);

namespace App\Interface\Http\Views\Owner;

use App\Application\Iam\IssueTelegramChatLinkToken\IssuedChatLinkToken;
use App\Application\Iam\ListOwnerTelegramChats\OwnerTelegramChatView as OwnerTelegramChatViewModel;
use DateTimeInterface;

/**
 * HTTP-проекция привязанного TG-чата и issued deep-link токена.
 * Поля — snake_case (контракт фронта); даты — ISO 8601.
 */
final readonly class OwnerTelegramChatView
{
    /**
     * @return array{
     *     id: string,
     *     chat_id: string,
     *     title: ?string,
     *     linked_at: string,
     * }
     */
    public static function fromView(OwnerTelegramChatViewModel $view): array
    {
        return [
            'id' => $view->id,
            'chat_id' => $view->chatId,
            'title' => $view->title,
            'linked_at' => $view->linkedAt->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array{ deep_link: string, expires_at: string }
     */
    public static function fromIssuedToken(IssuedChatLinkToken $token): array
    {
        return [
            'deep_link' => $token->deepLink,
            'expires_at' => $token->expiresAt->format(DateTimeInterface::ATOM),
        ];
    }
}
