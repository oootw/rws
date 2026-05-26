<?php

declare(strict_types=1);

use App\Application\Notifications\Logging\NotificationDeliveryStatus;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\NotificationDelivery;
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

it('показывает список доставок', function (): void {
    NotificationDelivery::query()->create([
        'owner_id' => null,
        'channel' => 'Email',
        'kind' => 'critical_error',
        'status' => NotificationDeliveryStatus::Delivered->value,
        'error' => null,
    ]);

    $this->get('/admin/notification-deliveries')->assertOk();
});

it('не выставляет страницу создания', function (): void {
    $this->get('/admin/notification-deliveries/create')->assertNotFound();
});

it('открывает карточку доставки с ошибкой', function (): void {
    $delivery = NotificationDelivery::query()->create([
        'owner_id' => null,
        'channel' => 'Telegram',
        'kind' => 'negative_review',
        'status' => NotificationDeliveryStatus::Failed->value,
        'error' => 'connection timeout',
    ]);

    $this->get("/admin/notification-deliveries/{$delivery->id}")
        ->assertOk()
        ->assertSee('connection timeout');
});
