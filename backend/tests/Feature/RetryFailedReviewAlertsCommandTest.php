<?php

declare(strict_types=1);

use App\Jobs\SendNegativeReviewAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('сообщает что упавших алертов нет', function (): void {
    $this->artisan('reviews:retry-failed-alerts')
        ->expectsOutput('Упавших алертов нет.')
        ->assertSuccessful();
});

it('показывает список упавших алертов с --list', function (): void {
    $reviewId = '11111111-1111-1111-1111-111111111111';
    insertFailedReviewAlertJob($reviewId);

    $this->artisan('reviews:retry-failed-alerts', ['--list' => true])
        ->expectsOutputToContain('Найдено упавших алертов: 1')
        ->expectsOutputToContain("reviewId={$reviewId}")
        ->assertSuccessful();

    expect(DB::table('failed_jobs')->count())->toBe(1);
});

it('перезапускает упавшие алерты и удаляет их из таблицы failed_jobs', function (): void {
    Queue::fake();

    $reviewId = '22222222-2222-2222-2222-222222222222';
    insertFailedReviewAlertJob($reviewId, queue: 'alerts');

    $this->artisan('reviews:retry-failed-alerts')
        ->expectsConfirmation('Перезапустить 1 алертов?', 'yes')
        ->assertSuccessful();

    Queue::assertPushed(SendNegativeReviewAlert::class, function (SendNegativeReviewAlert $job) use ($reviewId): bool {
        return $job->reviewId === $reviewId;
    });

    expect(DB::table('failed_jobs')->count())->toBe(0);
});

it('не перезапускает алерты если пользователь отказался', function (): void {
    Queue::fake();

    insertFailedReviewAlertJob('33333333-3333-3333-3333-333333333333');

    $this->artisan('reviews:retry-failed-alerts')
        ->expectsConfirmation('Перезапустить 1 алертов?', 'no')
        ->assertSuccessful();

    Queue::assertNothingPushed();
    expect(DB::table('failed_jobs')->count())->toBe(1);
});

it('удаляет упавшие алерты с --purge', function (): void {
    insertFailedReviewAlertJob('44444444-4444-4444-4444-444444444444');

    $this->artisan('reviews:retry-failed-alerts', ['--purge' => true])
        ->expectsConfirmation('Удалить эти записи из failed_jobs без перезапуска?', 'yes')
        ->expectsOutputToContain('Удалено: 1.')
        ->assertSuccessful();

    expect(DB::table('failed_jobs')->count())->toBe(0);
});

it('пропускает алерты с нераспознанным телом задачи', function (): void {
    Queue::fake();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => SendNegativeReviewAlert::class,
            'data' => ['command' => 'not-serialized'],
        ]),
        'exception' => 'Test exception',
        'failed_at' => now(),
    ]);

    $this->artisan('reviews:retry-failed-alerts')
        ->expectsConfirmation('Перезапустить 1 алертов?', 'yes')
        ->expectsOutputToContain('не смог распарсить reviewId')
        ->expectsOutputToContain('Перезапущено: 0. Пропущено: 1.')
        ->assertSuccessful();

    Queue::assertNothingPushed();
    expect(DB::table('failed_jobs')->count())->toBe(1);
});

it('не удаляет алерты при отказе от --purge', function (): void {
    insertFailedReviewAlertJob('55555555-5555-5555-5555-555555555555');

    $this->artisan('reviews:retry-failed-alerts', ['--purge' => true])
        ->expectsConfirmation('Удалить эти записи из failed_jobs без перезапуска?', 'no')
        ->assertSuccessful();

    expect(DB::table('failed_jobs')->count())->toBe(1);
});

it('пропускает задачу без сериализованной команды', function (): void {
    Queue::fake();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => SendNegativeReviewAlert::class,
            'data' => ['command' => ['broken' => true]],
        ]),
        'exception' => 'Test exception',
        'failed_at' => now(),
    ]);

    $this->artisan('reviews:retry-failed-alerts')
        ->expectsConfirmation('Перезапустить 1 алертов?', 'yes')
        ->expectsOutputToContain('Перезапущено: 0. Пропущено: 1.')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('не трогает упавшие задачи других классов', function (): void {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => 'App\\Jobs\\SomeOtherJob',
            'data' => [],
        ]),
        'exception' => 'Test exception',
        'failed_at' => now(),
    ]);

    $this->artisan('reviews:retry-failed-alerts')
        ->expectsOutput('Упавших алертов нет.')
        ->assertSuccessful();
});
