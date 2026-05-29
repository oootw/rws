<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Iam\Exceptions\TelegramChatNotOwnedByCaller;
use App\Application\Iam\IssueTelegramChatLinkToken\IssueTelegramChatLinkTokenCommand;
use App\Application\Iam\IssueTelegramChatLinkToken\IssueTelegramChatLinkTokenHandler;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsHandler;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsQuery;
use App\Application\Iam\UnlinkTelegramChat\UnlinkTelegramChatCommand;
use App\Application\Iam\UnlinkTelegramChat\UnlinkTelegramChatHandler;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Views\Owner\OwnerTelegramChatView;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Owner-панель: список привязанных групповых TG-чатов, выдача deep-link
 * токена и удаление привязки.
 *
 * Привязка ОТ кнопки до записи в БД делается ботом (фаза A3): фронт получает
 * deep-link, открывает его, владелец выбирает чат — Telegram присылает боту
 * `/start <token>`, бот зовёт BindTelegramChatHandler.
 *
 * 404 на удаление чужой строки кидаем по тому же принципу, что и в
 * OwnerPlacesController: не светим факт существования чужих привязок.
 */
final class OwnerTelegramChatsController
{
    public function __construct(
        private readonly ListOwnerTelegramChatsHandler $listChats,
        private readonly IssueTelegramChatLinkTokenHandler $issueLink,
        private readonly UnlinkTelegramChatHandler $unlinkChat,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $chats = $this->listChats->handle(
            new ListOwnerTelegramChatsQuery(ownerId: $ownerId->value),
        );

        return response()->json([
            'data' => array_map(OwnerTelegramChatView::fromView(...), $chats),
        ]);
    }

    public function issueLink(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $issued = $this->issueLink->handle(
            new IssueTelegramChatLinkTokenCommand(ownerId: $ownerId->value),
        );

        return response()->json([
            'data' => OwnerTelegramChatView::fromIssuedToken($issued),
        ], 201);
    }

    public function destroy(Request $request, string $chat): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        try {
            $this->unlinkChat->handle(new UnlinkTelegramChatCommand(
                ownerId: $ownerId->value,
                chatRowId: $chat,
            ));
        } catch (TelegramChatNotOwnedByCaller) {
            return ApiResponse::error(ApiErrorCode::TelegramChatNotFound, 404);
        }

        return response()->json(['data' => ['deleted' => true]]);
    }
}
