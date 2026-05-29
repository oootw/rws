<?php

declare(strict_types=1);

use App\Application\Notifications\OwnerNotification;
use App\Domain\Notifications\OwnerContact;
use App\Infrastructure\Notifications\Channels\TelegramDeliveryFailed;
use App\Infrastructure\Notifications\Channels\TelegramNotificationChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['nutgram.token' => '123456:TEST']);
    $this->app->forgetInstance(Nutgram::class);
    $this->app->forgetInstance('nutgram');
    $this->app->forgetInstance('telegram');
});

function ownerNotificationWith(OwnerContact $contact): OwnerNotification
{
    return new OwnerNotification(
        contact: $contact,
        text: '<b>Негативный отзыв</b>',
        emailSubject: 'Новый негативный отзыв',
        kind: 'negative_review',
    );
}

it('доставляет сообщение и в DM, и в каждый групповой чат', function (): void {
    /** @var Nutgram $bot */
    $bot = app(Nutgram::class);

    app(TelegramNotificationChannel::class)->deliver(ownerNotificationWith(
        new OwnerContact(
            telegramId: '3003',
            maxId: null,
            email: null,
            telegramChatIds: ['-1001111111111', '-1002222222222'],
        ),
    ));

    $bot->assertCalled('sendMessage', times: 3);
});

it('работает только с групповыми чатами без DM', function (): void {
    /** @var Nutgram $bot */
    $bot = app(Nutgram::class);

    app(TelegramNotificationChannel::class)->deliver(ownerNotificationWith(
        new OwnerContact(
            telegramId: null,
            maxId: null,
            email: null,
            telegramChatIds: ['-1001111111111'],
        ),
    ));

    $bot->assertCalled('sendMessage', times: 1);
});

it('считает доставленным, если хотя бы один target принял сообщение', function (): void {
    /** @var Nutgram $bot */
    $bot = app(Nutgram::class);
    // Первый таргет (DM) — 400 от Telegram. Второй (группа) — auto-fill
    // подставит валидный Message (FakeNutgram TypeFaker).
    $bot->willReceive(result: true, ok: false);

    app(TelegramNotificationChannel::class)->deliver(ownerNotificationWith(
        new OwnerContact(
            telegramId: '3003',
            maxId: null,
            email: null,
            telegramChatIds: ['-1001111111111'],
        ),
    ));

    $bot->assertCalled('sendMessage', times: 2);
});

it('бросает TelegramDeliveryFailed, если все таргеты упали', function (): void {
    /** @var Nutgram $bot */
    $bot = app(Nutgram::class);
    $bot->willReceive(result: true, ok: false);
    $bot->willReceive(result: true, ok: false);

    app(TelegramNotificationChannel::class)->deliver(ownerNotificationWith(
        new OwnerContact(
            telegramId: '3003',
            maxId: null,
            email: null,
            telegramChatIds: ['-1001111111111'],
        ),
    ));
})->throws(TelegramDeliveryFailed::class);

it('supports() = false, когда у контакта нет ни DM, ни групповых чатов', function (): void {
    /** @var TelegramNotificationChannel $channel */
    $channel = app(TelegramNotificationChannel::class);

    $notification = ownerNotificationWith(new OwnerContact(
        telegramId: null,
        maxId: null,
        email: 'owner@example.com',
    ));

    expect($channel->supports($notification))->toBeFalse();
});

it('supports() = false, когда бот не сконфигурирован', function (): void {
    config(['nutgram.token' => null]);
    $this->app->forgetInstance(Nutgram::class);
    $this->app->forgetInstance('nutgram');
    $this->app->forgetInstance('telegram');

    /** @var TelegramNotificationChannel $channel */
    $channel = app(TelegramNotificationChannel::class);

    $notification = ownerNotificationWith(new OwnerContact(
        telegramId: '3003',
        maxId: null,
        email: null,
        telegramChatIds: ['-1001111111111'],
    ));

    expect($channel->supports($notification))->toBeFalse();
});

it('supports() = true, когда есть только групповые чаты', function (): void {
    /** @var TelegramNotificationChannel $channel */
    $channel = app(TelegramNotificationChannel::class);

    $notification = ownerNotificationWith(new OwnerContact(
        telegramId: null,
        maxId: null,
        email: null,
        telegramChatIds: ['-1001111111111'],
    ));

    expect($channel->supports($notification))->toBeTrue();
});
