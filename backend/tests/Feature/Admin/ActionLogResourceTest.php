<?php

declare(strict_types=1);

use App\Domain\Analytics\ActionType;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\ActionLog;
use App\Models\Place;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

function createActionLog(ActionType $type, ?Place $place = null): ActionLog
{
    $place ??= Place::factory()->create();

    return ActionLog::query()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'place_id' => $place->id,
        'action_type' => $type->value,
        'metadata' => null,
        'created_at' => now(),
    ]);
}

it('показывает список записей журнала действий', function (): void {
    createActionLog(ActionType::Scanned);
    createActionLog(ActionType::LeftNegative);

    $this->get('/admin/action-logs')->assertOk();
});

it('не выставляет страницу создания', function (): void {
    $this->get('/admin/action-logs/create')->assertNotFound();
});

it('открывает карточку записи', function (): void {
    $log = createActionLog(ActionType::AdminDeletedReview);

    $this->get("/admin/action-logs/{$log->id}")->assertOk();
});
