<?php

declare(strict_types=1);

use App\Filament\Resources\Owners\Pages\ListOwners;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\Tariff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => 'admin',
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    // Без HTTP-маршрута Filament не знает, какая панель «текущая» — выставим вручную.
    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});

/**
 * Filament-таблицы тестируем через Livewire::test — оно даёт честный
 * прогон фильтров без необходимости рендерить blade и ловить markup.
 */
it('SelectFilter по тарифу показывает только владельцев нужного тарифа', function (): void {
    $mvp = Tariff::factory()->create(['title' => 'MVP']);
    $plus = Tariff::factory()->create(['title' => 'Plus']);

    $mvpUser = User::factory()->withTariff($mvp)->create(['name' => 'MVP-user']);
    $plusUser = User::factory()->withTariff($plus)->create(['name' => 'Plus-user']);

    Livewire::test(ListOwners::class)
        ->assertCanSeeTableRecords([$mvpUser, $plusUser])
        ->filterTable('tariff_id', $mvp->id)
        ->assertCanSeeTableRecords([$mvpUser])
        ->assertCanNotSeeTableRecords([$plusUser]);
});

it('TernaryFilter active показывает только владельцев с активной подпиской', function (): void {
    $active = User::factory()->create([
        'name' => 'Active',
        'subscription_ends_at' => now()->addDays(10),
    ]);
    $expired = User::factory()->withoutSubscription()->create(['name' => 'Expired']);
    $never = User::factory()->create([
        'name' => 'Never',
        'subscription_ends_at' => null,
    ]);

    Livewire::test(ListOwners::class)
        ->filterTable('subscription_status', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$expired, $never]);
});

it('TernaryFilter false показывает истёкших и без подписки', function (): void {
    $active = User::factory()->create(['subscription_ends_at' => now()->addDays(10)]);
    $expired = User::factory()->withoutSubscription()->create();
    $never = User::factory()->create(['subscription_ends_at' => null]);

    Livewire::test(ListOwners::class)
        ->filterTable('subscription_status', false)
        ->assertCanSeeTableRecords([$expired, $never])
        ->assertCanNotSeeTableRecords([$active]);
});

it('TernaryFilter null показывает всех', function (): void {
    $active = User::factory()->create(['subscription_ends_at' => now()->addDays(10)]);
    $expired = User::factory()->withoutSubscription()->create();

    Livewire::test(ListOwners::class)
        ->assertCanSeeTableRecords([$active, $expired]);
});

it('таблица показывает ключевые колонки', function (): void {
    $user = User::factory()->create([
        'name' => 'CheckColumns',
        'email' => 'columns@test.local',
        'subdomain_slug' => 'check-cols',
    ]);

    Livewire::test(ListOwners::class)
        ->assertSeeText('CheckColumns')
        ->assertSeeText('columns@test.local')
        ->assertSeeText('check-cols');
});
