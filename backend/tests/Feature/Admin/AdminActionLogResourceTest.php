<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use App\Models\AdminActionLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

function createAdminActionLog(string $action = 'extend_subscription'): AdminActionLog
{
    return AdminActionLog::query()->create([
        'id' => (string) Str::uuid(),
        'admin_email' => 'dev@test.local',
        'action' => $action,
        'resource' => 'App\\Filament\\Resources\\Owners\\Pages\\ListOwners',
        'record_id' => (string) Str::uuid(),
        'payload' => ['days' => 30],
        'ip' => '203.0.113.5',
        'user_agent' => 'phpunit',
        'created_at' => now(),
    ]);
}

it('показывает список аудит-записей', function (): void {
    createAdminActionLog('extend_subscription');
    createAdminActionLog('change_tariff');

    $this->get('/admin/admin-action-logs')->assertOk();
});

it('не выставляет страницу создания', function (): void {
    $this->get('/admin/admin-action-logs/create')->assertNotFound();
});

it('открывает карточку аудит-записи', function (): void {
    $log = createAdminActionLog();

    $this->get("/admin/admin-action-logs/{$log->id}")->assertOk();
});
