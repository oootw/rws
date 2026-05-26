<?php

declare(strict_types=1);

use App\Application\Jobs\FailedJobsActions;
use App\Application\Jobs\FailedJobsReader;
use App\Interface\Filament\Auth\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );
});

function insertFailedJob(string $jobClass = 'App\\Jobs\\SendNegativeReviewAlert', ?Carbon\CarbonInterface $failedAt = null): string
{
    $uuid = (string) Str::uuid();
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => $jobClass, 'data' => ['command' => 'O:8:"stdClass":0:{}']], JSON_THROW_ON_ERROR),
        'exception' => "RuntimeException: boom\n#0 ...",
        'failed_at' => ($failedAt ?? now())->toDateTimeString(),
    ]);

    return $uuid;
}

it('страница открывается и показывает счётчик', function (): void {
    insertFailedJob();
    insertFailedJob();

    $this->get('/admin/failed-jobs')
        ->assertOk()
        ->assertSee('Упавшие задачи');
});

it('Reader возвращает list<FailedJobView>', function (): void {
    insertFailedJob('App\\Jobs\\X');
    insertFailedJob('App\\Jobs\\Y');

    $jobs = app(FailedJobsReader::class)->all();

    expect($jobs)->toHaveCount(2)
        ->and($jobs[0]->exceptionFirstLine)->toBe('RuntimeException: boom');
});

it('Actions::delete удаляет запись', function (): void {
    $uuid = insertFailedJob();

    $deleted = app(FailedJobsActions::class)->delete($uuid);

    expect($deleted)->toBeTrue()
        ->and(DB::table('failed_jobs')->where('uuid', $uuid)->count())->toBe(0);
});

it('Actions::prune с порогом удаляет только старые', function (): void {
    insertFailedJob('A', now()->subDays(30));
    insertFailedJob('B', now()->subDays(2));

    $removed = app(FailedJobsActions::class)->prune(olderThanDays: 7);

    expect($removed)->toBe(1)
        ->and(DB::table('failed_jobs')->count())->toBe(1);
});

it('Actions::prune c 0 удаляет всё', function (): void {
    insertFailedJob();
    insertFailedJob();

    $removed = app(FailedJobsActions::class)->prune(olderThanDays: 0);

    expect($removed)->toBe(2)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});

it('Actions::retry вызывает queue:retry для uuid', function (): void {
    $uuid = insertFailedJob();

    $retried = app(FailedJobsActions::class)->retry($uuid);

    expect($retried)->toBeBool();
});
