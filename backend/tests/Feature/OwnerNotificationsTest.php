<?php

use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryHandler;
use App\Application\Iam\GetOwnerById\GetOwnerByIdHandler;
use App\Application\Notifications\ConfirmSubscriptionRenewed\ConfirmSubscriptionRenewedCommand;
use App\Application\Notifications\ConfirmSubscriptionRenewed\ConfirmSubscriptionRenewedHandler;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewCommand;
use App\Application\Notifications\NotifyAboutNegativeReview\NotifyAboutNegativeReviewHandler;
use App\Application\Notifications\RemindAboutSubscriptionExpiry\RemindAboutSubscriptionExpiryHandler;
use App\Application\Notifications\SendWeeklyDigest\SendWeeklyDigestCommand;
use App\Application\Notifications\SendWeeklyDigest\SendWeeklyDigestHandler;
use App\Domain\Analytics\WeeklySummary;
use App\Domain\Notifications\OwnerContact;
use App\Jobs\SendSubscriptionReminder;
use App\Jobs\SendWeeklyDigest;
use App\Mail\PlainTextMail;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use SergiX44\Nutgram\Nutgram;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->forgetInstance(Nutgram::class);
    $this->app->forgetInstance('nutgram');
    $this->app->forgetInstance('telegram');
});

it('доставляет уведомление о негативе через Telegram, когда бот сконфигурирован', function (): void {
    config(['nutgram.token' => '123456:TEST']);

    $bot = app(Nutgram::class);

    app(NotifyAboutNegativeReviewHandler::class)->handle(new NotifyAboutNegativeReviewCommand(
        contact: new OwnerContact(telegramId: '3003', maxId: null, email: null),
        reviewId: '11111111-1111-1111-1111-111111111111',
        placeTitle: 'Кафе',
        stars: 1,
        reviewText: 'Ужас',
        reviewerContact: 'test@mail.ru',
    ));

    $bot->assertCalled('sendMessage');
});

it('падает в e-mail, когда мессенджеры недоступны', function (): void {
    Mail::fake();
    config(['nutgram.token' => null]);

    app(NotifyAboutNegativeReviewHandler::class)->handle(new NotifyAboutNegativeReviewCommand(
        contact: new OwnerContact(telegramId: null, maxId: null, email: 'owner@example.com'),
        reviewId: '11111111-1111-1111-1111-111111111111',
        placeTitle: 'Кафе',
        stars: 2,
        reviewText: 'Плохо',
        reviewerContact: '+79990001122',
    ));

    Mail::assertSent(PlainTextMail::class, function (PlainTextMail $mail): bool {
        return $mail->hasTo('owner@example.com')
            && $mail->mailSubject === 'Новый негативный отзыв';
    });
});

it('weekly digest job отправляет дайджест по каждой точке владельца', function (): void {
    config(['nutgram.token' => '123456:TEST']);

    $owner = User::factory()->create(['telegram_id' => '4004']);
    Place::factory()->for($owner)->create(['title' => 'Бар']);

    $bot = app(Nutgram::class);

    app(SendWeeklyDigest::class)->handle(
        app(GetOwnerByIdHandler::class),
        app(GetWeeklySummaryHandler::class),
        app(SendWeeklyDigestHandler::class),
    );

    $bot->assertCalled('sendMessage');
});

it('форматирует дайджест с заголовком и метриками', function (): void {
    Mail::fake();

    app(SendWeeklyDigestHandler::class)->handle(new SendWeeklyDigestCommand(
        contact: new OwnerContact(telegramId: null, maxId: null, email: 'owner@example.com'),
        placeTitle: 'Бар',
        summary: new WeeklySummary(scanned: 10, redirectedExternal: 4, leftNegative: 2),
    ));

    Mail::assertSent(PlainTextMail::class, function (PlainTextMail $mail): bool {
        return str_contains($mail->bodyText, 'Сканирований: 10')
            && str_contains($mail->bodyText, 'Конверсия: 40%');
    });
});

it('напоминает о подписке за указанное число дней', function (): void {
    Mail::fake();
    config(['nutgram.token' => null]);

    User::factory()->create([
        'telegram_id' => null,
        'email' => 'owner@example.com',
        'subscription_ends_at' => now('Europe/Moscow')->addDays(3)->startOfDay()->utc(),
    ]);

    app(SendSubscriptionReminder::class)->handle(
        app(GetOwnerByIdHandler::class),
        app(RemindAboutSubscriptionExpiryHandler::class),
    );

    Mail::assertSent(PlainTextMail::class, function (PlainTextMail $mail): bool {
        return $mail->hasTo('owner@example.com')
            && str_contains($mail->mailSubject, 'подписк');
    });
});

it('не напоминает о подписке вне окна', function (): void {
    Mail::fake();

    User::factory()->create([
        'email' => 'owner@example.com',
        'subscription_ends_at' => now()->addDays(10),
    ]);

    app(SendSubscriptionReminder::class)->handle(
        app(GetOwnerByIdHandler::class),
        app(RemindAboutSubscriptionExpiryHandler::class),
    );

    Mail::assertNothingSent();
});

it('подтверждает продление подписки и пишет дату', function (): void {
    Mail::fake();

    app(ConfirmSubscriptionRenewedHandler::class)->handle(new ConfirmSubscriptionRenewedCommand(
        contact: new OwnerContact(telegramId: null, maxId: null, email: 'owner@example.com'),
        newExpiresAt: new DateTimeImmutable('2026-06-30T09:00:00Z'),
    ));

    Mail::assertSent(PlainTextMail::class, function (PlainTextMail $mail): bool {
        return $mail->mailSubject === 'Подписка Guard Reviews продлена'
            && str_contains($mail->bodyText, 'Подписка продлена!');
    });
});
