<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
    ]);
});

/**
 * Граничные проверки доступа к /admin/*.
 * Гость не должен проникать ни на один защищённый маршрут,
 * даже если знает конкретный {record}.
 */
it('гость → 302 на список владельцев', function (): void {
    $this->get('/admin/owners')->assertRedirect('/admin/login');
});

it('гость → 302 на создание владельца', function (): void {
    $this->get('/admin/owners/create')->assertRedirect('/admin/login');
});

it('гость → 302 на просмотр конкретного владельца', function (): void {
    $user = User::factory()->create();
    $this->get("/admin/owners/{$user->id}")->assertRedirect('/admin/login');
});

it('гость → 302 на редактирование конкретного владельца', function (): void {
    $user = User::factory()->create();
    $this->get("/admin/owners/{$user->id}/edit")->assertRedirect('/admin/login');
});

it('logout сбрасывает сессию админа', function (): void {
    $this->actingAs(
        new AdminUser([
            'id' => 'admin',
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    // После logout (POST /admin/logout) сессия должна быть очищена.
    $this->post('/admin/logout');

    $this->get('/admin/owners')->assertRedirect('/admin/login');
});

it('гарду web (другому) admin-сессия не даёт доступа в /admin', function (): void {
    // Логиним как обычного User по гарду web — это НЕ admin-гард.
    $owner = User::factory()->create();
    $this->actingAs($owner, 'web');

    $this->get('/admin/owners')->assertRedirect('/admin/login');
});

it('страница логина админки публичная', function (): void {
    $this->get('/admin/login')->assertOk();
});
