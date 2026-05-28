<?php

declare(strict_types=1);

use App\Application\Shared\Events\DomainEventDispatcher;
use App\Application\Shared\Transactions\TransactionRunner;
use App\Domain\Iam\OwnerChatLinkToken;
use App\Domain\Iam\OwnerChatLinkTokenId;
use App\Domain\Iam\OwnerChatLinkTokenIdGenerator;
use App\Domain\Iam\OwnerChatLinkTokenRepository;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\OwnerTelegramChatIdGenerator;
use App\Domain\Iam\OwnerTelegramChatRepository;
use App\Domain\Iam\TelegramChatId;
use App\Domain\Shared\Clock\Clock;
use App\Domain\Shared\Events\DomainEvent;

/**
 * @param  list<OwnerTelegramChat>  $initial
 */
function fakeOwnerTelegramChatRepository(array $initial = []): OwnerTelegramChatRepository
{
    return new class($initial) implements OwnerTelegramChatRepository
    {
        /** @var list<OwnerTelegramChat> */
        public array $chats;

        /** @param  list<OwnerTelegramChat>  $initial */
        public function __construct(array $initial)
        {
            $this->chats = $initial;
        }

        public function save(OwnerTelegramChat $chat): void
        {
            foreach ($this->chats as $index => $stored) {
                if ($stored->id->equals($chat->id)) {
                    $this->chats[$index] = $chat;

                    return;
                }
            }

            $this->chats[] = $chat;
        }

        public function findById(OwnerTelegramChatId $id): ?OwnerTelegramChat
        {
            foreach ($this->chats as $chat) {
                if ($chat->id->equals($id)) {
                    return $chat;
                }
            }

            return null;
        }

        public function findByOwnerAndChat(OwnerId $ownerId, TelegramChatId $chatId): ?OwnerTelegramChat
        {
            foreach ($this->chats as $chat) {
                if ($chat->ownerId->equals($ownerId) && $chat->chatId->equals($chatId)) {
                    return $chat;
                }
            }

            return null;
        }

        public function listByOwner(OwnerId $ownerId): array
        {
            return array_values(array_filter(
                $this->chats,
                static fn (OwnerTelegramChat $c) => $c->ownerId->equals($ownerId),
            ));
        }

        public function delete(OwnerTelegramChatId $id): void
        {
            $this->chats = array_values(array_filter(
                $this->chats,
                static fn (OwnerTelegramChat $c) => ! $c->id->equals($id),
            ));
        }
    };
}

/**
 * @param  list<string>  $ids
 */
function fakeOwnerTelegramChatIdGenerator(array $ids): OwnerTelegramChatIdGenerator
{
    return new class($ids) implements OwnerTelegramChatIdGenerator
    {
        /** @var list<string> */
        private array $ids;

        /** @param  list<string>  $ids */
        public function __construct(array $ids)
        {
            $this->ids = $ids;
        }

        public function next(): OwnerTelegramChatId
        {
            $value = array_shift($this->ids) ?? throw new RuntimeException('No more fake chat ids');

            return new OwnerTelegramChatId($value);
        }
    };
}

/**
 * @param  list<OwnerChatLinkToken>  $initial
 */
function fakeOwnerChatLinkTokenRepository(array $initial = [], ?Clock $clock = null): OwnerChatLinkTokenRepository
{
    return new class($initial, $clock) implements OwnerChatLinkTokenRepository
    {
        /** @var list<OwnerChatLinkToken> */
        public array $tokens;

        /** @param  list<OwnerChatLinkToken>  $initial */
        public function __construct(array $initial, private ?Clock $clock)
        {
            $this->tokens = $initial;
        }

        public function save(OwnerChatLinkToken $token): void
        {
            foreach ($this->tokens as $index => $stored) {
                if ($stored->id->equals($token->id)) {
                    $this->tokens[$index] = $token;

                    return;
                }
            }

            $this->tokens[] = $token;
        }

        public function findActiveByToken(string $token): ?OwnerChatLinkToken
        {
            $now = $this->clock?->now();

            foreach ($this->tokens as $stored) {
                if ($stored->token !== $token) {
                    continue;
                }
                if ($stored->isConsumed()) {
                    continue;
                }
                if ($now !== null && $stored->isExpiredAt($now)) {
                    continue;
                }

                return $stored;
            }

            return null;
        }

        public function findById(OwnerChatLinkTokenId $id): ?OwnerChatLinkToken
        {
            foreach ($this->tokens as $stored) {
                if ($stored->id->equals($id)) {
                    return $stored;
                }
            }

            return null;
        }
    };
}

function fakeOwnerChatLinkTokenIdGenerator(string $value): OwnerChatLinkTokenIdGenerator
{
    return new class($value) implements OwnerChatLinkTokenIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): OwnerChatLinkTokenId
        {
            return new OwnerChatLinkTokenId($this->value);
        }
    };
}

function passThroughTransactionRunner(): TransactionRunner
{
    return new class implements TransactionRunner
    {
        public function run(Closure $callback): mixed
        {
            return $callback();
        }
    };
}

function collectingDomainEventDispatcher(): DomainEventDispatcher
{
    return new class implements DomainEventDispatcher
    {
        /** @var list<DomainEvent> */
        public array $events = [];

        public function dispatchAll(iterable $events): void
        {
            foreach ($events as $event) {
                $this->events[] = $event;
            }
        }
    };
}
