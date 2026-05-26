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
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});

it('bulk_extend_subscription продлевает подписку у выбранных владельцев', function (): void {
    $alice = User::factory()->create(['subscription_ends_at' => null]);
    $bob = User::factory()->create(['subscription_ends_at' => now()->subDay()]);

    Livewire::test(ListOwners::class)
        ->callTableBulkAction('bulk_extend_subscription', [$alice, $bob], data: [
            'days' => 30,
        ])
        ->assertNotified();

    expect($alice->refresh()->subscription_ends_at)->not->toBeNull()
        ->and($alice->subscription_ends_at->isFuture())->toBeTrue()
        ->and($bob->refresh()->subscription_ends_at->isFuture())->toBeTrue();
});

it('bulk_change_tariff меняет тариф у выбранных владельцев', function (): void {
    $tariff = Tariff::factory()->create();
    $alice = User::factory()->create(['tariff_id' => null]);
    $bob = User::factory()->create(['tariff_id' => null]);

    Livewire::test(ListOwners::class)
        ->callTableBulkAction('bulk_change_tariff', [$alice, $bob], data: [
            'tariff_id' => $tariff->id,
        ])
        ->assertNotified();

    expect($alice->refresh()->tariff_id)->toBe($tariff->id)
        ->and($bob->refresh()->tariff_id)->toBe($tariff->id);
});

it('bulk_change_tariff с пустым значением сбрасывает тариф', function (): void {
    $tariff = Tariff::factory()->create();
    $alice = User::factory()->create(['tariff_id' => $tariff->id]);

    Livewire::test(ListOwners::class)
        ->callTableBulkAction('bulk_change_tariff', [$alice], data: [
            'tariff_id' => null,
        ])
        ->assertNotified();

    expect($alice->refresh()->tariff_id)->toBeNull();
});
