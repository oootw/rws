<?php

declare(strict_types=1);

use App\Domain\Iam\Events\OwnerTelegramChatLinked;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerTelegramChat;
use App\Domain\Iam\OwnerTelegramChatId;
use App\Domain\Iam\TelegramChatId;

function linkedOwnerTelegramChat(?string $title = 'Команда «Кафе»'): OwnerTelegramChat
{
    return OwnerTelegramChat::link(
        id: new OwnerTelegramChatId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        chatId: new TelegramChatId('-1001234567890'),
        title: $title,
        linkedAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );
}

it('фабрика link() заполняет поля и записывает доменное событие', function (): void {
    $chat = linkedOwnerTelegramChat();

    expect($chat->id->value)->toBe('11111111-1111-1111-1111-111111111111')
        ->and($chat->ownerId->value)->toBe('22222222-2222-2222-2222-222222222222')
        ->and($chat->chatId->value)->toBe('-1001234567890')
        ->and($chat->title())->toBe('Команда «Кафе»')
        ->and($chat->linkedAt->format('c'))->toBe('2026-06-01T12:00:00+00:00');

    $events = $chat->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(OwnerTelegramChatLinked::class)
        ->and($events[0]->id->value)->toBe($chat->id->value)
        ->and($events[0]->ownerId->value)->toBe($chat->ownerId->value)
        ->and($events[0]->chatId->value)->toBe($chat->chatId->value);
});

it('повторный pullRecordedEvents возвращает пустой список', function (): void {
    $chat = linkedOwnerTelegramChat();
    $chat->pullRecordedEvents();

    expect($chat->pullRecordedEvents())->toBe([]);
});

it('фабрика restore() не порождает событий', function (): void {
    $chat = OwnerTelegramChat::restore(
        id: new OwnerTelegramChatId('11111111-1111-1111-1111-111111111111'),
        ownerId: new OwnerId('22222222-2222-2222-2222-222222222222'),
        chatId: new TelegramChatId('-100500'),
        title: null,
        linkedAt: new DateTimeImmutable('2026-06-01T12:00:00Z'),
    );

    expect($chat->pullRecordedEvents())->toBe([])
        ->and($chat->title())->toBeNull();
});

it('rename() меняет title', function (): void {
    $chat = linkedOwnerTelegramChat(title: 'Старое имя');

    $chat->rename('Новое имя');
    expect($chat->title())->toBe('Новое имя');

    $chat->rename(null);
    expect($chat->title())->toBeNull();
});
