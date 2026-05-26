<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use App\Models\AdminActionLog;
use Filament\Actions\Action;
use Filament\Actions\Events\ActionCalled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
    ]);
});

it('пишет аудит-запись при ActionCalled событии Filament', function (): void {
    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    $action = Action::make('extend_subscription');

    \Illuminate\Support\Facades\Event::dispatch(ActionCalled::class, $action);

    $log = AdminActionLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('extend_subscription')
        ->and($log->admin_email)->toBe('dev@test.local');
});

it('не пишет запись без аутентифицированного админа', function (): void {
    $action = Action::make('refresh');

    \Illuminate\Support\Facades\Event::dispatch(ActionCalled::class, $action);

    expect(AdminActionLog::query()->count())->toBe(0);
});
