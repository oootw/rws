<?php

declare(strict_types=1);

use App\Application\Iam\BindTelegramChat\BindTelegramChatCommand;
use App\Application\Iam\BindTelegramChat\BindTelegramChatHandler;
use App\Application\Iam\Exceptions\ChatLinkTokenNotFound;
use App\Application\Iam\Exceptions\TelegramChatNotOwnedByCaller;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\IssueTelegramChatLinkToken\IssueTelegramChatLinkTokenCommand;
use App\Application\Iam\IssueTelegramChatLinkToken\IssueTelegramChatLinkTokenHandler;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsHandler;
use App\Application\Iam\ListOwnerTelegramChats\ListOwnerTelegramChatsQuery;
use App\Application\Iam\UnlinkTelegramChat\UnlinkTelegramChatCommand;
use App\Application\Iam\UnlinkTelegramChat\UnlinkTelegramChatHandler;
use App\Domain\Iam\ChatLinkTokenAlreadyConsumed;
use App\Domain\Iam\ChatLinkTokenExpired;
use App\Domain\Iam\Events\OwnerTelegramChatLinked;
use App\Domain\Iam\OwnerChatLinkToken;
use App\Domain\Iam\OwnerChatLinkTokenId;
use App\Domain\Iam\OwnerChatLinkTokenRepository;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\TelegramChatId;
use Illuminate\Config\Repository as ConfigRepository;
use Random\Engine\Mt19937;
use Random\Randomizer;

function chatLinkConfig(int $ttl = 600, string $botUsername = 'guardreviews_bot'): ConfigRepository
{
    return new ConfigRepository([
        'guardreviews.chat_link.ttl_seconds' => $ttl,
        'guardreviews.telegram.bot_username' => $botUsername,
    ]);
}

it('выдаёт chat-link токен и собирает deep-link', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);
    $tokens = fakeOwnerChatLinkTokenRepository();

    $issued = (new IssueTelegramChatLinkTokenHandler(
        owners: $owners,
        tokens: $tokens,
        ids: fakeOwnerChatLinkTokenIdGenerator('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        clock: frozenClockAt('2026-06-01T12:00:00Z'),
        randomizer: new Randomizer(new Mt19937(42)),
        config: chatLinkConfig(ttl: 600),
    ))->handle(new IssueTelegramChatLinkTokenCommand(ownerId: $owner->id->value));

    expect($issued->deepLink)->toStartWith('https://t.me/guardreviews_bot?startgroup=')
        ->and($issued->expiresAt->format('c'))->toBe('2026-06-01T12:10:00+00:00')
        ->and($tokens->tokens)->toHaveCount(1)
        ->and($tokens->tokens[0]->ownerId->value)->toBe($owner->id->value);

    $token = substr($issued->deepLink, strlen('https://t.me/guardreviews_bot?startgroup='));
    expect($token)->toMatch('/^[a-f0-9]{32}$/');
});

it('бросает TenantNotFound при выдаче токена для неизвестного owner-а', function (): void {
    (new IssueTelegramChatLinkTokenHandler(
        owners: fakeOwnerRepository(),
        tokens: fakeOwnerChatLinkTokenRepository(),
        ids: fakeOwnerChatLinkTokenIdGenerator('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        clock: frozenClockAt('2026-06-01T12:00:00Z'),
        randomizer: new Randomizer(new Mt19937(42)),
        config: chatLinkConfig(),
    ))->handle(new IssueTelegramChatLinkTokenCommand(ownerId: '00000000-0000-0000-0000-000000000000'));
})->throws(TenantNotFound::class);

it('биндит чат: консьюмит токен, создаёт OwnerTelegramChat и публикует событие', function (): void {
    $clock = frozenClockAt('2026-06-01T12:01:00Z');
    $token = OwnerChatLinkToken::issue(
        id: new OwnerChatLinkTokenId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: restoredOwner()->id,
        token: 'cafef00dcafef00dcafef00dcafef00d',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 600,
    );
    $tokens = fakeOwnerChatLinkTokenRepository([$token], $clock);
    $chats = fakeOwnerTelegramChatRepository();
    $events = collectingDomainEventDispatcher();

    (new BindTelegramChatHandler(
        tokens: $tokens,
        chats: $chats,
        ids: fakeOwnerTelegramChatIdGenerator(['bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb']),
        clock: $clock,
        transactions: passThroughTransactionRunner(),
        events: $events,
    ))->handle(new BindTelegramChatCommand(
        token: 'cafef00dcafef00dcafef00dcafef00d',
        chatId: '-1001234567890',
        title: 'Команда «Кафе»',
    ));

    expect($tokens->tokens[0]->isConsumed())->toBeTrue()
        ->and($chats->chats)->toHaveCount(1)
        ->and($chats->chats[0]->id->value)->toBe('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb')
        ->and($chats->chats[0]->chatId->value)->toBe('-1001234567890')
        ->and($chats->chats[0]->title())->toBe('Команда «Кафе»')
        ->and($events->events)->toHaveCount(1)
        ->and($events->events[0])->toBeInstanceOf(OwnerTelegramChatLinked::class);
});

it('идемпотентен по (owner, chat): повторный bind обновляет title без дубля', function (): void {
    $clock = frozenClockAt('2026-06-01T12:01:00Z');
    $existing = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
        ownerId: restoredOwner()->id,
        chatId: new TelegramChatId('-1001234567890'),
        title: 'Старое имя',
        linkedAt: new DateTimeImmutable('2026-06-01T11:00:00Z'),
    );
    $token = OwnerChatLinkToken::issue(
        id: new OwnerChatLinkTokenId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: restoredOwner()->id,
        token: 'cafef00dcafef00dcafef00dcafef00d',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 600,
    );

    $tokens = fakeOwnerChatLinkTokenRepository([$token], $clock);
    $chats = fakeOwnerTelegramChatRepository([$existing]);
    $events = collectingDomainEventDispatcher();

    (new BindTelegramChatHandler(
        tokens: $tokens,
        chats: $chats,
        ids: fakeOwnerTelegramChatIdGenerator(['unused']),
        clock: $clock,
        transactions: passThroughTransactionRunner(),
        events: $events,
    ))->handle(new BindTelegramChatCommand(
        token: 'cafef00dcafef00dcafef00dcafef00d',
        chatId: '-1001234567890',
        title: 'Новое имя',
    ));

    expect($chats->chats)->toHaveCount(1)
        ->and($chats->chats[0]->id->value)->toBe('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb')
        ->and($chats->chats[0]->title())->toBe('Новое имя')
        ->and($events->events)->toBe([]);
});

it('бросает ChatLinkTokenNotFound для неизвестного токена', function (): void {
    (new BindTelegramChatHandler(
        tokens: fakeOwnerChatLinkTokenRepository([], frozenClockAt('2026-06-01T12:00:00Z')),
        chats: fakeOwnerTelegramChatRepository(),
        ids: fakeOwnerTelegramChatIdGenerator(['unused']),
        clock: frozenClockAt('2026-06-01T12:00:00Z'),
        transactions: passThroughTransactionRunner(),
        events: collectingDomainEventDispatcher(),
    ))->handle(new BindTelegramChatCommand(
        token: 'deadbeefdeadbeefdeadbeefdeadbeef',
        chatId: '-100500',
        title: null,
    ));
})->throws(ChatLinkTokenNotFound::class);

it('бросает ChatLinkTokenExpired для протухшего токена', function (): void {
    /*
     * Реальный EloquentRepository отфильтрует истёкший токен и вернёт null
     * (consumer увидит ChatLinkTokenNotFound). Здесь — race-condition:
     * репозиторий ослаблен, агрегат сам ловит истечение.
     */
    $clock = frozenClockAt('2026-06-01T13:00:00Z');
    $token = OwnerChatLinkToken::issue(
        id: new OwnerChatLinkTokenId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: restoredOwner()->id,
        token: 'cafef00dcafef00dcafef00dcafef00d',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 60,
    );

    $tokens = new class([$token]) implements OwnerChatLinkTokenRepository
    {
        /** @param  list<OwnerChatLinkToken>  $tokens */
        public function __construct(public array $tokens) {}

        public function save(OwnerChatLinkToken $token): void {}

        public function findActiveByToken(string $token): ?OwnerChatLinkToken
        {
            foreach ($this->tokens as $t) {
                if ($t->token === $token) {
                    return $t;
                }
            }

            return null;
        }

        public function findById(OwnerChatLinkTokenId $id): ?OwnerChatLinkToken
        {
            return null;
        }
    };

    (new BindTelegramChatHandler(
        tokens: $tokens,
        chats: fakeOwnerTelegramChatRepository(),
        ids: fakeOwnerTelegramChatIdGenerator(['unused']),
        clock: $clock,
        transactions: passThroughTransactionRunner(),
        events: collectingDomainEventDispatcher(),
    ))->handle(new BindTelegramChatCommand(
        token: 'cafef00dcafef00dcafef00dcafef00d',
        chatId: '-100500',
        title: null,
    ));
})->throws(ChatLinkTokenExpired::class);

it('повторное использование токена → ChatLinkTokenAlreadyConsumed', function (): void {
    $clock = frozenClockAt('2026-06-01T12:01:00Z');
    $token = OwnerChatLinkToken::issue(
        id: new OwnerChatLinkTokenId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: restoredOwner()->id,
        token: 'cafef00dcafef00dcafef00dcafef00d',
        now: new DateTimeImmutable('2026-06-01T12:00:00Z'),
        ttlSeconds: 600,
    );
    $token->consume(new DateTimeImmutable('2026-06-01T12:00:30Z'));

    $tokens = new class([$token]) implements OwnerChatLinkTokenRepository
    {
        public function __construct(public array $tokens) {}

        public function save(OwnerChatLinkToken $token): void {}

        public function findActiveByToken(string $token): ?OwnerChatLinkToken
        {
            foreach ($this->tokens as $t) {
                if ($t->token === $token) {
                    return $t;
                }
            }

            return null;
        }

        public function findById(OwnerChatLinkTokenId $id): ?OwnerChatLinkToken
        {
            return null;
        }
    };

    (new BindTelegramChatHandler(
        tokens: $tokens,
        chats: fakeOwnerTelegramChatRepository(),
        ids: fakeOwnerTelegramChatIdGenerator(['unused']),
        clock: $clock,
        transactions: passThroughTransactionRunner(),
        events: collectingDomainEventDispatcher(),
    ))->handle(new BindTelegramChatCommand(
        token: 'cafef00dcafef00dcafef00dcafef00d',
        chatId: '-100500',
        title: null,
    ));
})->throws(ChatLinkTokenAlreadyConsumed::class);

it('возвращает чаты владельца как list<OwnerTelegramChatView>', function (): void {
    $ownerId = restoredOwner()->id;
    $otherOwnerId = new OwnerId('33333333-3333-3333-3333-333333333333');

    $a = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: $ownerId,
        chatId: new TelegramChatId('-100500'),
        title: 'Кафе',
        linkedAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );
    $b = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb'),
        ownerId: $ownerId,
        chatId: new TelegramChatId('-100501'),
        title: null,
        linkedAt: new DateTimeImmutable('2026-06-02T12:00:00Z'),
    );
    $foreign = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('cccccccc-cccc-cccc-cccc-cccccccccccc'),
        ownerId: $otherOwnerId,
        chatId: new TelegramChatId('-100502'),
        title: null,
        linkedAt: new DateTimeImmutable('2026-06-03T12:00:00Z'),
    );

    $views = (new ListOwnerTelegramChatsHandler(
        chats: fakeOwnerTelegramChatRepository([$a, $b, $foreign]),
    ))->handle(new ListOwnerTelegramChatsQuery(ownerId: $ownerId->value));

    expect($views)->toHaveCount(2)
        ->and(array_map(fn ($v) => $v->chatId, $views))->toBe(['-100500', '-100501'])
        ->and($views[0]->title)->toBe('Кафе')
        ->and($views[1]->title)->toBeNull();
});

it('удаляет чат владельца', function (): void {
    $chat = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: restoredOwner()->id,
        chatId: new TelegramChatId('-100500'),
        title: null,
        linkedAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );
    $chats = fakeOwnerTelegramChatRepository([$chat]);

    (new UnlinkTelegramChatHandler($chats))->handle(new UnlinkTelegramChatCommand(
        ownerId: restoredOwner()->id->value,
        chatRowId: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    ));

    expect($chats->chats)->toBe([]);
});

it('запрещает удалять чужой чат → TelegramChatNotOwnedByCaller', function (): void {
    $chat = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'),
        ownerId: new OwnerId('33333333-3333-3333-3333-333333333333'),
        chatId: new TelegramChatId('-100500'),
        title: null,
        linkedAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );

    (new UnlinkTelegramChatHandler(
        fakeOwnerTelegramChatRepository([$chat]),
    ))->handle(new UnlinkTelegramChatCommand(
        ownerId: restoredOwner()->id->value,
        chatRowId: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    ));
})->throws(TelegramChatNotOwnedByCaller::class);

it('бросает TelegramChatNotOwnedByCaller если строки не существует', function (): void {
    (new UnlinkTelegramChatHandler(
        fakeOwnerTelegramChatRepository(),
    ))->handle(new UnlinkTelegramChatCommand(
        ownerId: restoredOwner()->id->value,
        chatRowId: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    ));
})->throws(TelegramChatNotOwnedByCaller::class);
