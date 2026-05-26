<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\TariffNotFound;
use App\Application\Iam\SetDefaultTariff\SetDefaultTariffCommand;
use App\Application\Iam\SetDefaultTariff\SetDefaultTariffHandler;
use App\Filament\Resources\Tariffs\Pages\ListTariffs;
use App\Filament\Resources\Tariffs\Pages\ViewTariff;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\Tariff;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'guardreviews.admin.email' => 'dev@test.local',
        'guardreviews.admin.password_hash' => Hash::make('test-password-strong-12'),
        'guardreviews.admin.name' => 'Test Dev',
    ]);

    $this->actingAs(
        new AdminUser([
            'id' => AdminUser::ID,
            'email' => 'dev@test.local',
            'name' => 'Test Dev',
            'password' => Hash::make('test-password-strong-12'),
        ]),
        'admin',
    );

    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});

// — HTTP smoke —

it('показывает список тарифов', function (): void {
    Tariff::factory()->count(3)->sequence(
        ['title' => 'Альфа-тариф'],
        ['title' => 'Бета-тариф'],
        ['title' => 'Гамма-тариф'],
    )->create();

    $this->get('/admin/tariffs')
        ->assertOk()
        ->assertSee('Альфа-тариф')
        ->assertSee('Бета-тариф');
});

it('открывает страницу создания тарифа', function (): void {
    $this->get('/admin/tariffs/create')->assertOk();
});

it('открывает карточку и форму редактирования тарифа', function (): void {
    $tariff = Tariff::factory()->create(['title' => 'EditMe']);

    $this->get("/admin/tariffs/{$tariff->id}")->assertOk()->assertSee('EditMe');
    $this->get("/admin/tariffs/{$tariff->id}/edit")->assertOk();
});

// — Use case интеграция —

it('SetDefaultTariffHandler делает указанный тариф default, остальные сбрасывает', function (): void {
    $mvp = Tariff::factory()->create(['title' => 'MVP', 'is_default' => true]);
    $plus = Tariff::factory()->create(['title' => 'Plus', 'is_default' => false]);

    app(SetDefaultTariffHandler::class)->handle(
        new SetDefaultTariffCommand(tariffId: (string) $plus->id),
    );

    expect($plus->refresh()->is_default)->toBeTrue()
        ->and($mvp->refresh()->is_default)->toBeFalse();
});

it('SetDefaultTariffHandler гарантирует ровно один is_default в БД', function (): void {
    Tariff::factory()->count(3)->sequence(
        ['title' => 'A', 'is_default' => true],
        ['title' => 'B', 'is_default' => true],
        ['title' => 'C', 'is_default' => false],
    )->create();

    $c = Tariff::query()->where('title', 'C')->firstOrFail();

    app(SetDefaultTariffHandler::class)->handle(
        new SetDefaultTariffCommand(tariffId: (string) $c->id),
    );

    expect(Tariff::query()->where('is_default', true)->count())->toBe(1);
});

it('SetDefaultTariffHandler с несуществующим id бросает TariffNotFound', function (): void {
    expect(fn () => app(SetDefaultTariffHandler::class)->handle(
        new SetDefaultTariffCommand(tariffId: '00000000-0000-0000-0000-000000000000'),
    ))->toThrow(TariffNotFound::class);
});

it('ViewTariff set_default назначает тариф default', function (): void {
    $currentDefault = Tariff::factory()->create(['title' => 'Default', 'is_default' => true]);
    $candidate = Tariff::factory()->create(['title' => 'Candidate', 'is_default' => false]);

    Livewire::test(ViewTariff::class, ['record' => $candidate->getRouteKey()])
        ->callAction('set_default')
        ->assertNotified();

    expect($candidate->refresh()->is_default)->toBeTrue()
        ->and($currentDefault->refresh()->is_default)->toBeFalse();
});

it('ListTariffs callTableAction set_default переключает default', function (): void {
    $currentDefault = Tariff::factory()->create(['is_default' => true]);
    $candidate = Tariff::factory()->create(['is_default' => false]);

    Livewire::test(ListTariffs::class)
        ->callTableAction('set_default', $candidate)
        ->assertNotified();

    expect($candidate->refresh()->is_default)->toBeTrue()
        ->and($currentDefault->refresh()->is_default)->toBeFalse();
});
