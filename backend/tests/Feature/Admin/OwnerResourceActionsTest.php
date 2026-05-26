<?php

declare(strict_types=1);

use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
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

it('CreateOwner создаёт владельца через RegisterOwnerHandler', function (): void {
    Tariff::factory()->create(['title' => 'MVP', 'is_default' => true]);

    Livewire::test(CreateOwner::class)
        ->fillForm([
            'name' => 'Новый владелец',
            'email' => 'new-owner@test.local',
            'subdomain_slug' => 'new-cafe',
            'telegram_id' => '987654321',
            'tariff_id' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $owner = User::query()->where('subdomain_slug', 'new-cafe')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->name)->toBe('Новый владелец')
        ->and($owner->email)->toBe('new-owner@test.local')
        ->and($owner->telegram_id)->toBe('987654321');
});

it('CreateOwner применяет выбранный тариф, если он отличается от default', function (): void {
    $default = Tariff::factory()->create(['title' => 'MVP', 'is_default' => true]);
    $premium = Tariff::factory()->create(['title' => 'Premium', 'is_default' => false]);

    Livewire::test(CreateOwner::class)
        ->fillForm([
            'name' => 'Premium Owner',
            'email' => 'premium@test.local',
            'subdomain_slug' => 'premium-cafe',
            'telegram_id' => null,
            'tariff_id' => $premium->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $owner = User::query()->where('subdomain_slug', 'premium-cafe')->first();

    expect($owner)->not->toBeNull()
        ->and((string) $owner->tariff_id)->toBe((string) $premium->id)
        ->and((string) $owner->tariff_id)->not->toBe((string) $default->id);
});

it('EditOwner сохраняет изменения через UpdateOwnerProfileHandler', function (): void {
    $user = User::factory()->create([
        'name' => 'Старое имя',
        'email' => 'old@test.local',
        'subdomain_slug' => 'old-slug',
        'telegram_id' => null,
    ]);

    Livewire::test(EditOwner::class, ['record' => $user->getRouteKey()])
        ->fillForm([
            'name' => 'Новое имя',
            'email' => 'new@test.local',
            'subdomain_slug' => 'new-slug',
            'telegram_id' => '555001',
            'tariff_id' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $user->refresh();

    expect($user->name)->toBe('Новое имя')
        ->and($user->email)->toBe('new@test.local')
        ->and($user->subdomain_slug)->toBe('new-slug')
        ->and($user->telegram_id)->toBe('555001');
});

it('EditOwner delete удаляет владельца через DeleteOwnerHandler', function (): void {
    $user = User::factory()->create(['name' => 'Удаляемый']);

    Livewire::test(EditOwner::class, ['record' => $user->getRouteKey()])
        ->callAction('delete')
        ->assertNotified();

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});
