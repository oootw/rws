<?php

use App\Domain\Analytics\ActionType;
use App\Enums\ReviewStatus;
use App\Jobs\SendNegativeReviewAlert;
use App\Mail\PlainTextMail;
use App\Models\ActionLog;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('возвращает публичные данные точки при активной подписке', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'cafe']);
    $place = Place::factory()->for($user)->create(['title' => 'Cafe Uyut']);

    $response = $this->getJson(publicPlaceUrl($place), tenantHeaders($user));

    $response->assertOk()
        ->assertJsonPath('data.title', 'Cafe Uyut')
        ->assertJsonPath('data.subscription_active', true)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'platforms', 'privacy_url', 'captcha_client_key'],
        ]);
});

it('блокирует публичные маршруты при истёкшей подписке', function (): void {
    $user = User::factory()->withoutSubscription()->create(['subdomain_slug' => 'expired']);
    $place = Place::factory()->for($user)->create();

    $this->getJson(publicPlaceUrl($place), tenantHeaders($user))
        ->assertForbidden()
        ->assertJsonPath('code', 'subscription_expired')
        ->assertJsonPath('message', 'Сервис временно недоступен. Подписка не активна.');
});

it('записывает действие сканирования', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'scan']);
    $place = Place::factory()->for($user)->create();

    $this->postJson(publicPlaceUrl($place, '/scan'), [], tenantHeaders($user))
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(ActionLog::query()->where('place_id', $place->id)->count())->toBe(1)
        ->and(ActionLog::query()->first()->action_type)->toBe(ActionType::Scanned);
});

it('перенаправляет на площадку и пишет метаданные', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'redirect']);
    $place = Place::factory()->for($user)->create();

    $this->postJson(publicPlaceUrl($place, '/redirect'), [
        'platform_type' => '2gis',
    ], tenantHeaders($user))
        ->assertOk()
        ->assertJsonPath('url', 'https://2gis.ru/firm/example');

    $log = ActionLog::query()->where('place_id', $place->id)->first();

    expect($log?->action_type)->toBe(ActionType::RedirectedExternal)
        ->and($log?->metadata)->toBe(['platform' => '2gis']);
});

it('создаёт негативный отзыв и ставит задачу алерта в очередь', function (): void {
    Queue::fake();
    $user = User::factory()->create(['subdomain_slug' => 'review']);
    $place = Place::factory()->for($user)->create();

    $this->postJson(publicPlaceUrl($place, '/reviews'), [
        'stars' => 2,
        'text' => 'Long wait time',
        'contact' => '+79990001122',
        'consent_accepted' => true,
        'captcha_token' => 'test-token',
    ], tenantHeaders($user))
        ->assertOk()
        ->assertJson(['ok' => true]);

    $review = Review::query()->first();

    expect($review)->not->toBeNull()
        ->and($review->stars)->toBe(2)
        ->and($review->status)->toBe(ReviewStatus::New);

    Queue::assertPushed(SendNegativeReviewAlert::class);
});

it('отклоняет отзыв с пустым токеном captcha', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'captcha']);
    $place = Place::factory()->for($user)->create();

    $this->postJson(publicPlaceUrl($place, '/reviews'), [
        'stars' => 1,
        'text' => 'Bad service',
        'contact' => 'user@example.com',
        'consent_accepted' => true,
        'captcha_token' => '',
    ], tenantHeaders($user))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['captcha_token'])
        ->assertJsonPath('errors.captcha_token.0', 'Подтвердите, что вы не робот.');
});

it('уведомляет владельца и основателя о критической ошибке', function (): void {
    Mail::fake();
    config(['guardreviews.admin_alert_email' => 'founder@example.com']);

    $user = User::factory()->create([
        'subdomain_slug' => 'critical',
        'email' => 'owner@example.com',
    ]);
    $place = Place::factory()->for($user)->withoutPlatforms()->create();

    $this->postJson(publicPlaceUrl($place, '/critical-error'), [
        'context' => 'no_platforms',
    ], tenantHeaders($user))
        ->assertOk()
        ->assertJson(['ok' => true]);

    Mail::assertSent(PlainTextMail::class, 2);
});

it('возвращает «не найдено» для чужого арендатора', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'owner']);
    $intruder = User::factory()->create(['subdomain_slug' => 'intruder']);
    $place = Place::factory()->for($owner)->create();

    $this->getJson(publicPlaceUrl($place), tenantHeaders($intruder))
        ->assertNotFound()
        ->assertJsonPath('code', 'place_not_found')
        ->assertJsonPath('message', 'Заведение не найдено.');
});
