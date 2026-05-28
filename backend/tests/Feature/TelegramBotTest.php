<?php

use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewCommand;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler;
use App\Domain\Iam\SubdomainSlug;
use App\Domain\Notifications\OwnerContact;
use App\Enums\ReviewStatus;
use App\Models\Place;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\User;
use Database\Seeders\TariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    resetTelegramBot();
    config(['nutgram.token' => '123456:TEST']);
});

it('валидирует правила адреса поддомена', function (): void {
    expect(fn () => new SubdomainSlug('ab'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new SubdomainSlug('api'))->toThrow(InvalidArgumentException::class);
    expect((new SubdomainSlug('valid-name'))->value)->toBe('valid-name');
});

it('завершает регистрацию и создаёт владельца', function (): void {
    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser())
        ->setCommonChat(fakeTelegramChat());

    $bot->hearMessage(['text' => '/start']);
    $bot->run();

    $bot->hearText('owner@example.com');
    $bot->run();

    $bot->hearText('my-cafe');
    $bot->run();

    $owner = User::query()->where('telegram_id', '1001')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->email)->toBe('owner@example.com')
        ->and($owner->subdomain_slug)->toBe('my-cafe');
});

it('показывает главное меню уже зарегистрированному владельцу', function (): void {
    User::factory()->create(['telegram_id' => '1001']);

    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser())
        ->setCommonChat(fakeTelegramChat());

    $bot->hearMessage(['text' => '/start']);
    $bot->run();

    $bot->assertCalled('sendMessage');
    assertTelegramReplyContains($bot, 'Главное меню:');
});

it('отклоняет некорректный email при регистрации', function (): void {
    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser())
        ->setCommonChat(fakeTelegramChat());

    $bot->hearMessage(['text' => '/start']);
    $bot->run();

    $bot->hearText('not-an-email');
    $bot->run();

    assertTelegramReplyContains($bot, 'Некорректный email.');
    expect(User::query()->where('telegram_id', '1001')->exists())->toBeFalse();
});

it('отклоняет занятый поддомен при регистрации', function (): void {
    User::factory()->create(['subdomain_slug' => 'my-cafe']);

    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser())
        ->setCommonChat(fakeTelegramChat());

    $bot->hearMessage(['text' => '/start']);
    $bot->run();

    $bot->hearText('owner@example.com');
    $bot->run();

    $bot->hearText('my-cafe');
    $bot->run();

    assertTelegramReplyContains($bot, 'уже занят');
    expect(User::query()->where('telegram_id', '1001')->exists())->toBeFalse();
});

it('создаёт точку через диалог /addplace', function (): void {
    $owner = User::factory()->create(['telegram_id' => '4004']);

    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser(4004))
        ->setCommonChat(fakeTelegramChat(4004));

    $bot->hearMessage(['text' => '/addplace']);
    $bot->run();

    $bot->hearText('Кафе Уют');
    $bot->run();

    $bot->hearText('https://2gis.ru/firm/example');
    $bot->run();

    $bot->hearText('-');
    $bot->run();

    $bot->hearText('-');
    $bot->run();

    $place = Place::query()->where('user_id', $owner->id)->first();

    expect($place)->not->toBeNull()
        ->and($place->title)->toBe('Кафе Уют')
        ->and($place->platforms)->toHaveCount(1);

    assertTelegramReplyContains($bot, 'создана');
});

it('обновляет статус отзыва из обратного вызова', function (): void {
    $owner = User::factory()->create(['telegram_id' => '2002']);
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 2,
        'contact' => '+79990001122',
        'text' => 'Плохо',
        'status' => ReviewStatus::New,
    ]);

    registeredTelegramBot(2002)
        ->hearCallbackQueryData("review:{$review->id}:in_progress")
        ->run();

    expect($review->fresh()->status)->toBe(ReviewStatus::InProgress);
});

it('отправляет алерт о негативном отзыве в Telegram', function (): void {
    $owner = User::factory()->create(['telegram_id' => '3003']);
    $place = Place::factory()->for($owner)->create(['title' => 'Кафе']);
    $review = Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 1,
        'contact' => 'test@mail.ru',
        'text' => 'Ужас',
    ]);

    $bot = telegramBot();

    app(NotifyAboutNegativeReviewHandler::class)->handle(new NotifyAboutNegativeReviewCommand(
        contact: new OwnerContact(
            telegramId: (string) $owner->telegram_id,
            maxId: null,
            email: null,
        ),
        reviewId: (string) $review->id,
        placeTitle: (string) $place->title,
        stars: (int) $review->stars,
        reviewText: (string) $review->text,
        reviewerContact: (string) $review->contact,
    ));

    $bot->assertCalled('sendMessage');
});

it('требует регистрацию для защищённых команд', function (): void {
    $bot = telegramBot()
        ->setCommonUser(fakeTelegramUser(5005))
        ->setCommonChat(fakeTelegramChat(5005));

    $bot->hearMessage(['text' => '/places']);
    $bot->run();

    $bot->assertReplyText('Сначала пройдите регистрацию: /start');
});

it('показывает пустой список точек зарегистрированному владельцу', function (): void {
    User::factory()->create(['telegram_id' => '6006']);

    $bot = registeredTelegramBot(6006);
    $bot->hearMessage(['text' => '/places']);
    $bot->run();

    $bot->assertReplyText('У вас пока нет точек. Создайте первую: /addplace');
});

it('показывает точки владельца со встроенной клавиатурой', function (): void {
    $owner = User::factory()->create(['telegram_id' => '7007']);
    Place::factory()->for($owner)->create(['title' => 'Кофейня']);

    $bot = registeredTelegramBot(7007);
    $bot->hearMessage(['text' => '/places']);
    $bot->run();

    $bot->assertCalled('sendMessage');
});

it('показывает сообщение об отсутствии отзывов', function (): void {
    User::factory()->create(['telegram_id' => '8008']);

    $bot = registeredTelegramBot(8008);
    $bot->hearMessage(['text' => '/reviews']);
    $bot->run();

    $bot->assertReplyText('Негативных отзывов пока нет.');
});

it('показывает последние отзывы владельца', function (): void {
    $owner = User::factory()->create(['telegram_id' => '9009']);
    $place = Place::factory()->for($owner)->create(['title' => 'Бар']);
    Review::factory()->create([
        'place_id' => $place->id,
        'stars' => 2,
        'text' => 'Плохое обслуживание',
        'contact' => '+79990001122',
    ]);

    $bot = registeredTelegramBot(9009);
    $bot->hearMessage(['text' => '/reviews']);
    $bot->run();

    assertTelegramReplyContains($bot, 'Бар');
    assertTelegramReplyContains($bot, 'Плохое обслуживание');
});

it('показывает статус активной подписки', function (): void {
    User::factory()->create([
        'telegram_id' => '1010',
        'subscription_ends_at' => now()->addDays(10),
    ]);

    $bot = registeredTelegramBot(1010);
    $bot->hearMessage(['text' => '/subscription']);
    $bot->run();

    assertTelegramReplyContains($bot, 'активна до');
});

it('показывает статус неактивной подписки', function (): void {
    User::factory()->withoutSubscription()->create(['telegram_id' => '1111']);

    $bot = registeredTelegramBot(1111);
    $bot->hearMessage(['text' => '/subscription']);
    $bot->run();

    assertTelegramReplyContains($bot, 'не активна');
});

it('возвращает ссылку на оплату из команды /pay', function (): void {
    $this->seed(TariffSeeder::class);

    config([
        'guardreviews.tinkoff.terminal_key' => 'TestTerminal',
        'guardreviews.tinkoff.secret_key' => 'test-secret',
        'guardreviews.tinkoff.api_url' => 'https://securepay.tinkoff.ru/v2',
    ]);

    Http::fake([
        'https://securepay.tinkoff.ru/v2/Init' => Http::response([
            'Success' => true,
            'PaymentId' => 999,
            'PaymentURL' => 'https://pay.tinkoff.test/session',
        ]),
    ]);

    $user = User::factory()->create(['telegram_id' => '1212']);
    $tariff = Tariff::query()->where('title', 'MVP')->firstOrFail();
    $user->update(['tariff_id' => $tariff->id]);

    $bot = registeredTelegramBot(1212);
    $bot->hearMessage(['text' => '/pay']);
    $bot->run();

    assertTelegramReplyContains($bot, 'pay.tinkoff.test/session');
});

it('отвечает на команду /link', function (): void {
    User::factory()->create(['telegram_id' => '1313']);

    $bot = registeredTelegramBot(1313);
    $bot->hearMessage(['text' => '/link']);
    $bot->run();

    assertTelegramReplyContains($bot, 'Привязка MAX');
});

it('показывает информацию о точке из обратного вызова', function (): void {
    $owner = User::factory()->create(['telegram_id' => '1414']);
    $place = Place::factory()->for($owner)->withoutPlatforms()->create(['title' => 'Пекарня']);

    $bot = registeredTelegramBot(1414);
    $bot->hearCallbackQueryData("place:info:{$place->id}");
    $bot->run();

    assertTelegramReplyContains($bot, 'Пекарня');
});

it('сообщает что точка не найдена в обратном вызове place:info', function (): void {
    User::factory()->create(['telegram_id' => '1515']);

    $bot = registeredTelegramBot(1515);
    $bot->hearCallbackQueryData('place:info:'.Str::uuid());
    $bot->run();

    $bot->assertReplyText('Точка не найдена.');
});

it('отправляет недельную аналитику из обратного вызова', function (): void {
    $owner = User::factory()->create(['telegram_id' => '1616']);
    $place = Place::factory()->for($owner)->create(['title' => 'Суши-бар']);

    $bot = registeredTelegramBot(1616);
    $bot->hearCallbackQueryData("place:analytics:{$place->id}");
    $bot->run();

    $bot->assertCalled('sendMessage');
});

it('отправляет QR-код из обратного вызова', function (): void {
    $owner = User::factory()->create(['telegram_id' => '1717']);
    $place = Place::factory()->for($owner)->create(['title' => 'Пиццерия']);

    $bot = registeredTelegramBot(1717);
    $bot->hearCallbackQueryData("place:qr:{$place->id}");
    $bot->run();

    $bot->assertCalled('sendPhoto');
})->skip(fn (): bool => ! extension_loaded('gd'), 'GD extension не установлен');

it('требует регистрацию для обратного вызова отзыва', function (): void {
    $bot = telegramBot()
        ->setCommonUser(fakeTelegramUser(1818))
        ->setCommonChat(fakeTelegramChat(1818));

    $bot->hearCallbackQueryData('review:'.Str::uuid().':in_progress');
    $bot->run();

    $bot->assertCalled('answerCallbackQuery');
});

it('отклоняет некорректный статус отзыва в обратном вызове', function (): void {
    User::factory()->create(['telegram_id' => '1919']);

    $bot = registeredTelegramBot(1919);
    $bot->hearCallbackQueryData('review:'.Str::uuid().':bad_status');
    $bot->run();

    $bot->assertCalled('answerCallbackQuery');
});

it('сообщает что отзыв не найден в обратном вызове', function (): void {
    User::factory()->create(['telegram_id' => '2020']);

    $bot = registeredTelegramBot(2020);
    $bot->hearCallbackQueryData('review:'.Str::uuid().':resolved');
    $bot->run();

    $bot->assertCalled('answerCallbackQuery');
});

it('отклоняет обратный вызов отзыва от другого владельца', function (): void {
    $owner = User::factory()->create(['telegram_id' => '2121']);
    User::factory()->create(['telegram_id' => '2122']);
    $place = Place::factory()->for($owner)->create();
    $review = Review::factory()->create(['place_id' => $place->id]);
    $initialStatus = $review->status;

    $bot = registeredTelegramBot(2122);
    $bot->hearCallbackQueryData("review:{$review->id}:resolved");
    $bot->run();

    $bot->assertCalled('answerCallbackQuery');
    expect($review->fresh()->status)->toBe($initialStatus);
});

it('отклоняет пустое название точки в /addplace', function (): void {
    User::factory()->create(['telegram_id' => '3001']);

    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser(3001))
        ->setCommonChat(fakeTelegramChat(3001));

    $bot->hearMessage(['text' => '/addplace']);
    $bot->run();

    $bot->hearText('   ');
    $bot->run();

    assertTelegramReplyContains($bot, 'не может быть пустым');
    expect(Place::query()->count())->toBe(0);
});

it('отклоняет некорректный URL площадки в /addplace', function (): void {
    User::factory()->create(['telegram_id' => '3002']);

    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser(3002))
        ->setCommonChat(fakeTelegramChat(3002));

    $bot->hearMessage(['text' => '/addplace']);
    $bot->run();

    $bot->hearText('Кафе');
    $bot->run();

    $bot->hearText('not-a-url');
    $bot->run();

    assertTelegramReplyContains($bot, 'Некорректная ссылка');
    expect(Place::query()->count())->toBe(0);
});

it('требует регистрацию в начале диалога /addplace', function (): void {
    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser(3003))
        ->setCommonChat(fakeTelegramChat(3003));

    $bot->hearMessage(['text' => '/addplace']);
    $bot->run();

    $bot->assertReplyText('Сначала пройдите регистрацию: /start');
});

it('показывает ошибку оплаты если Tinkoff не настроен', function (): void {
    User::factory()->create(['telegram_id' => '3004']);

    config([
        'guardreviews.tinkoff.terminal_key' => null,
        'guardreviews.tinkoff.secret_key' => null,
    ]);

    $bot = registeredTelegramBot(3004);
    $bot->hearMessage(['text' => '/pay']);
    $bot->run();

    assertTelegramReplyContains($bot, 'недоступна');
});

it('отклоняет некорректный поддомен при регистрации', function (): void {
    $bot = telegramBot()
        ->willStartConversation()
        ->setCommonUser(fakeTelegramUser(3005))
        ->setCommonChat(fakeTelegramChat(3005));

    $bot->hearMessage(['text' => '/start']);
    $bot->run();

    $bot->hearText('owner@example.com');
    $bot->run();

    $bot->hearText('ab');
    $bot->run();

    assertTelegramReplyContains($bot, '3–32');
    expect(User::query()->where('telegram_id', '3005')->exists())->toBeFalse();
});
