<?php

declare(strict_types=1);

use App\Interface\Filament\Auth\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function actAsAdmin(): void
{
    test()->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );
}

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
    ]);
});

it('пустой allow-list пропускает любые IP', function (): void {
    config(['guardreviews.admin.allowed_ips' => '']);
    actAsAdmin();

    $this->get('/admin/owners', ['REMOTE_ADDR' => '198.51.100.10'])->assertOk();
});

it('IP вне allow-list получает 403', function (): void {
    config(['guardreviews.admin.allowed_ips' => '203.0.113.5']);
    actAsAdmin();

    $this->call('GET', '/admin/owners', [], [], [], ['REMOTE_ADDR' => '198.51.100.10'])
        ->assertForbidden();
});

it('IP в allow-list пропускается', function (): void {
    config(['guardreviews.admin.allowed_ips' => '203.0.113.5,198.51.100.0/24']);
    actAsAdmin();

    $this->call('GET', '/admin/owners', [], [], [], ['REMOTE_ADDR' => '198.51.100.42'])
        ->assertOk();
});
