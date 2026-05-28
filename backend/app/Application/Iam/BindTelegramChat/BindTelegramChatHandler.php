<?php

declare(strict_types=1);

namespace App\Application\Iam\BindTelegramChat;

use App\Application\Iam\Exceptions\ChatLinkTokenNotFound;
use App\Application\Shared\Events\DomainEventDispatcher;
use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Iam\OwnerChatLinkTokenRepository;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatIdGenerator;
use App\Domain\Iam\OwnerTelegramChatRepository;
use App\Domain\Iam\TelegramChatId;
use App\Domain\Shared\Clock\Clock;

/**
 * Use case: бот, увидев `/start <token>` в группе, консьюмит токен и
 * создаёт привязку OwnerTelegramChat. Идемпотентен по (owner_id, chat_id):
 * если такая привязка уже есть — просто обновляет title и не плодит дубликатов.
 *
 * Доменные исключения ChatLinkTokenExpired / ChatLinkTokenAlreadyConsumed
 * пробрасываются наверх — бот переведёт их в человекочитаемое сообщение в чате.
 */
final readonly class BindTelegramChatHandler
{
    public function __construct(
        private OwnerChatLinkTokenRepository $tokens,
        private OwnerTelegramChatRepository $chats,
        private OwnerTelegramChatIdGenerator $ids,
        private Clock $clock,
        private TransactionRunner $transactions,
        private DomainEventDispatcher $events,
    ) {}

    public function handle(BindTelegramChatCommand $command): void
    {
        $chatId = new TelegramChatId($command->chatId);

        $this->transactions->run(function () use ($command, $chatId): void {
            $token = $this->tokens->findActiveByToken($command->token);

            if ($token === null) {
                throw new ChatLinkTokenNotFound;
            }

            $now = $this->clock->now();
            $token->consume($now);
            $this->tokens->save($token);

            $existing = $this->chats->findByOwnerAndChat($token->ownerId, $chatId);

            if ($existing !== null) {
                $existing->rename($command->title);
                $this->chats->save($existing);

                return;
            }

            $chat = OwnerTelegramChat::link(
                id: $this->ids->next(),
                ownerId: $token->ownerId,
                chatId: $chatId,
                title: $command->title,
                linkedAt: $now,
            );

            $this->chats->save($chat);
            $this->events->dispatchAll($chat->pullRecordedEvents());
        });
    }
}
