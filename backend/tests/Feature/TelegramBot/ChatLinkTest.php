<?php

declare(strict_types=1);

use App\Interface\TelegramBot\Support\TelegramMessages;
use App\Models\OwnerChatLinkToken;
use App\Models\OwnerTelegramChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Telegram\Properties\UpdateType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User as TelegramUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    resetTelegramBot();
    config(['nutgram.token' => '123456:TEST']);
});

function groupChat(int $id = -1001234567890, string $title = 'Команда'): Chat
{
    return Chat::make(id: $id, type: 'group', title: $title);
}

function issueChatLinkToken(User $owner, string $token, ?DateTimeInterface $expiresAt = null): OwnerChatLinkToken
{
    return OwnerChatLinkToken::query()->create([
        'owner_id' => $owner->id,
        'token' => $token,
        'expires_at' => $expiresAt ?? now()->addMinutes(10),
        'consumed_at' => null,
        'created_at' => now(),
    ]);
}

it('привязывает групповой чат по /start <token>', function (): void {
    $owner = User::factory()->create(['telegram_id' => '5005']);
    $token = str_repeat('a', 32);
    issueChatLinkToken($owner, $token);

    $bot = telegramBot()
        ->setCommonUser(TelegramUser::make(id: 5005, is_bot: false, first_name: 'Владелец'))
        ->setCommonChat(groupChat());

    $bot->hearMessage(['text' => "/start {$token}"]);
    $bot->run();

    $row = OwnerTelegramChat::query()->where('owner_id', $owner->id)->first();

    expect($row)->not->toBeNull()
        ->and($row->chat_id)->toBe('-1001234567890')
        ->and($row->title)->toBe('Команда');

    assertTelegramReplyContains($bot, TelegramMessages::chatLinked());
});

it('идемпотентен: повторный bind того же чата не плодит дубликаты', function (): void {
    $owner = User::factory()->create(['telegram_id' => '6006']);

    $firstToken = str_repeat('b', 32);
    issueChatLinkToken($owner, $firstToken);

    $bot = telegramBot()
        ->setCommonUser(TelegramUser::make(id: 6006, is_bot: false, first_name: 'Владелец'))
        ->setCommonChat(groupChat(title: 'Старое имя'));

    $bot->hearMessage(['text' => "/start {$firstToken}"]);
    $bot->run();

    // Второй deep-link (свежий токен), тот же chat_id, новый title.
    $secondToken = str_repeat('c', 32);
    issueChatLinkToken($owner, $secondToken);

    $bot->setCommonChat(groupChat(title: 'Новое имя'));
    $bot->hearMessage(['text' => "/start {$secondToken}"]);
    $bot->run();

    $rows = OwnerTelegramChat::query()->where('owner_id', $owner->id)->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->title)->toBe('Новое имя');
});

it('сообщает об ошибке при истёкшем токене и не создаёт привязку', function (): void {
    $owner = User::factory()->create(['telegram_id' => '7007']);
    $token = str_repeat('d', 32);
    issueChatLinkToken($owner, $token, now()->subMinute());

    $bot = telegramBot()
        ->setCommonUser(TelegramUser::make(id: 7007, is_bot: false, first_name: 'Владелец'))
        ->setCommonChat(groupChat());

    $bot->hearMessage(['text' => "/start {$token}"]);
    $bot->run();

    expect(OwnerTelegramChat::query()->count())->toBe(0);
    assertTelegramReplyContains($bot, TelegramMessages::chatLinkInvalid());
});

it('игнорирует /start <token> в личке: не привязывает чат', function (): void {
    $owner = User::factory()->create(['telegram_id' => '8008']);
    $token = str_repeat('e', 32);
    issueChatLinkToken($owner, $token);

    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(TelegramUser::make(id: 8008, is_bot: false, first_name: 'Владелец'))
        ->setCommonChat(Chat::make(id: 8008, type: 'private'));

    $bot->hearMessage(['text' => "/start {$token}"]);
    $bot->run();

    expect(OwnerTelegramChat::query()->count())->toBe(0);
});

it('шлёт подсказку при добавлении бота в группу (my_chat_member)', function (): void {
    $bot = telegramBot()
        ->setCommonChat(groupChat());

    $bot->hearUpdateType(UpdateType::MY_CHAT_MEMBER, [
        'old_chat_member' => ['status' => 'left'],
        'new_chat_member' => ['status' => 'member'],
    ]);
    $bot->run();

    assertTelegramReplyContains($bot, TelegramMessages::chatLinkHint());
});
