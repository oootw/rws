<?php

declare(strict_types=1);

use App\Application\Admin\RecordAdminAction\RecordAdminActionCommand;
use App\Application\Admin\RecordAdminAction\RecordAdminActionHandler;
use App\Domain\Admin\AdminActionLog;
use App\Domain\Admin\AdminActionLogId;
use App\Domain\Admin\AdminActionLogIdGenerator;
use App\Domain\Admin\AdminActionLogRepository;

function fakeAdminActionLogRepository(): AdminActionLogRepository
{
    return new class implements AdminActionLogRepository
    {
        /** @var list<AdminActionLog> */
        public array $saved = [];

        public function save(AdminActionLog $log): void
        {
            $this->saved[] = $log;
        }
    };
}

function fakeAdminActionLogIdGenerator(string $value = '11111111-1111-1111-1111-111111111111'): AdminActionLogIdGenerator
{
    return new class($value) implements AdminActionLogIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): AdminActionLogId
        {
            return new AdminActionLogId($this->value);
        }
    };
}

it('записывает аудит-запись с метаданными запроса', function (): void {
    $repo = fakeAdminActionLogRepository();

    (new RecordAdminActionHandler(
        logs: $repo,
        ids: fakeAdminActionLogIdGenerator(),
        clock: frozenClockAt('2026-05-26 10:00:00'),
    ))->handle(new RecordAdminActionCommand(
        adminEmail: 'admin@example.com',
        action: 'extend_subscription',
        resource: 'App\\Filament\\Resources\\Owners\\Pages\\ListOwners',
        recordId: 'owner-uuid',
        payload: ['days' => 30],
        ip: '203.0.113.5',
        userAgent: 'Mozilla/5.0',
    ));

    expect($repo->saved)->toHaveCount(1);

    $log = $repo->saved[0];

    expect($log->adminEmail)->toBe('admin@example.com')
        ->and($log->action)->toBe('extend_subscription')
        ->and($log->resource)->toBe('App\\Filament\\Resources\\Owners\\Pages\\ListOwners')
        ->and($log->recordId)->toBe('owner-uuid')
        ->and($log->payload)->toBe(['days' => 30])
        ->and($log->ip)->toBe('203.0.113.5')
        ->and($log->userAgent)->toBe('Mozilla/5.0')
        ->and($log->recordedAt->format('Y-m-d H:i:s'))->toBe('2026-05-26 10:00:00');
});

it('допускает запись без опциональных полей', function (): void {
    $repo = fakeAdminActionLogRepository();

    (new RecordAdminActionHandler(
        logs: $repo,
        ids: fakeAdminActionLogIdGenerator(),
        clock: frozenClockAt('2026-05-26 10:00:00'),
    ))->handle(new RecordAdminActionCommand(
        adminEmail: 'admin@example.com',
        action: 'refresh',
    ));

    expect($repo->saved[0]->resource)->toBeNull()
        ->and($repo->saved[0]->recordId)->toBeNull()
        ->and($repo->saved[0]->payload)->toBeNull();
});
