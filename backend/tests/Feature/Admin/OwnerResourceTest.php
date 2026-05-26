<?php

declare(strict_types=1);

use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffCommand;
use App\Application\Iam\ChangeOwnerTariff\ChangeOwnerTariffHandler;
use App\Application\Iam\DeleteOwner\DeleteOwnerCommand;
use App\Application\Iam\DeleteOwner\DeleteOwnerHandler;
use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Iam\Exceptions\TariffNotFound;
use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionCommand;
use App\Application\Iam\ExtendSubscription\ExtendSubscriptionHandler;
use App\Application\Iam\OverrideSubscription\OverrideSubscriptionCommand;
use App\Application\Iam\OverrideSubscription\OverrideSubscriptionHandler;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileCommand;
use App\Application\Iam\UpdateOwnerProfile\UpdateOwnerProfileHandler;
use App\Interface\Filament\Auth\AdminUser;
use App\Models\PaymentTransaction;
use App\Models\Place;
use App\Models\Review;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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
});

it('показывает список владельцев', function (): void {
    User::factory()->count(3)->sequence(
        ['name' => 'Alpha'],
        ['name' => 'Beta'],
        ['name' => 'Gamma'],
    )->create();

    $this->get('/admin/owners')
        ->assertOk()
        ->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertSee('Gamma');
});

it('открывает страницу создания владельца', function (): void {
    $this->get('/admin/owners/create')->assertOk();
});

it('открывает карточку владельца', function (): void {
    $user = User::factory()->create(['name' => 'Карточка']);

    $this->get("/admin/owners/{$user->id}")
        ->assertOk()
        ->assertSee('Карточка');
});

it('открывает форму редактирования', function (): void {
    $user = User::factory()->create(['name' => 'Editable']);

    $this->get("/admin/owners/{$user->id}/edit")
        ->assertOk()
        ->assertSee('Editable');
});

// — Интеграционные проверки use cases поверх реального Eloquent —

it('UpdateOwnerProfileHandler пишет изменения в БД через репозиторий', function (): void {
    $user = User::factory()->create([
        'name' => 'Старое имя',
        'subdomain_slug' => 'old-slug',
    ]);

    app(UpdateOwnerProfileHandler::class)->handle(new UpdateOwnerProfileCommand(
        ownerId: (string) $user->id,
        name: 'Новое имя',
        email: 'new@test.local',
        subdomain: 'new-slug',
        telegramId: '12345',
        tariffId: null,
    ));

    $user->refresh();

    expect($user->name)->toBe('Новое имя')
        ->and($user->email)->toBe('new@test.local')
        ->and($user->subdomain_slug)->toBe('new-slug')
        ->and($user->telegram_id)->toBe('12345');
});

it('OverrideSubscriptionHandler пишет дату подписки', function (): void {
    $user = User::factory()->create();
    $futureDate = new DateTimeImmutable('+90 days');

    app(OverrideSubscriptionHandler::class)->handle(new OverrideSubscriptionCommand(
        ownerId: (string) $user->id,
        endsAt: $futureDate,
    ));

    $user->refresh();

    expect($user->subscription_ends_at->format('Y-m-d'))->toBe($futureDate->format('Y-m-d'));
});

it('DeleteOwnerHandler удаляет владельца и каскадно связанные сущности', function (): void {
    $user = User::factory()->create();
    $place = Place::factory()->for($user)->create();
    Review::query()->create([
        'id' => '11111111-1111-1111-1111-111111111111',
        'place_id' => $place->id,
        'stars' => 2,
        'text' => 'bad',
        'contact' => 'x@test.local',
        'status' => 'new',
    ]);

    app(DeleteOwnerHandler::class)->handle(
        new DeleteOwnerCommand(ownerId: (string) $user->id),
    );

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(Place::query()->whereKey($place->id)->exists())->toBeFalse()
        ->and(Review::query()->where('place_id', $place->id)->exists())->toBeFalse();
});

// — Edge cases use cases поверх Eloquent —

it('DeleteOwnerHandler идемпотентен: второй вызов бросает TenantNotFound', function (): void {
    $user = User::factory()->create();
    $handler = app(DeleteOwnerHandler::class);

    $handler->handle(new DeleteOwnerCommand(ownerId: (string) $user->id));

    expect(fn () => $handler->handle(new DeleteOwnerCommand(ownerId: (string) $user->id)))
        ->toThrow(TenantNotFound::class);
});

it('UpdateOwnerProfileHandler запрещает переименование на занятый поддомен', function (): void {
    User::factory()->create(['subdomain_slug' => 'taken-slug']);
    $user = User::factory()->create(['subdomain_slug' => 'free-slug']);

    expect(fn () => app(UpdateOwnerProfileHandler::class)->handle(
        new UpdateOwnerProfileCommand(
            ownerId: (string) $user->id,
            name: 'Name',
            email: 'x@y.io',
            subdomain: 'taken-slug',
            telegramId: null,
            tariffId: null,
        ),
    ))->toThrow(SubdomainAlreadyTaken::class);
});

it('UpdateOwnerProfileHandler разрешает оставить тот же поддомен (нет переименования)', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'stable-slug', 'name' => 'Old']);

    app(UpdateOwnerProfileHandler::class)->handle(new UpdateOwnerProfileCommand(
        ownerId: (string) $user->id,
        name: 'New',
        email: $user->email,
        subdomain: 'stable-slug',
        telegramId: null,
        tariffId: null,
    ));

    $user->refresh();
    expect($user->name)->toBe('New')
        ->and($user->subdomain_slug)->toBe('stable-slug');
});

it('UpdateOwnerProfileHandler с несуществующим ownerId бросает TenantNotFound', function (): void {
    expect(fn () => app(UpdateOwnerProfileHandler::class)->handle(
        new UpdateOwnerProfileCommand(
            ownerId: '00000000-0000-0000-0000-000000000000',
            name: 'X',
            email: 'a@b.io',
            subdomain: 'whatever',
            telegramId: null,
            tariffId: null,
        ),
    ))->toThrow(TenantNotFound::class);
});

it('OverrideSubscriptionHandler сбрасывает подписку при null', function (): void {
    $user = User::factory()->create(['subscription_ends_at' => now()->addDays(30)]);

    app(OverrideSubscriptionHandler::class)->handle(new OverrideSubscriptionCommand(
        ownerId: (string) $user->id,
        endsAt: null,
    ));

    $user->refresh();
    expect($user->subscription_ends_at)->toBeNull();
});

it('ExtendSubscriptionHandler от активной подписки накапливает срок', function (): void {
    $user = User::factory()->create(['subscription_ends_at' => now()->addDays(10)->startOfDay()]);
    $originalEndsAt = $user->subscription_ends_at;

    app(ExtendSubscriptionHandler::class)->handle(new ExtendSubscriptionCommand(
        ownerId: (string) $user->id,
        durationDays: 14,
    ));

    $user->refresh();
    expect($user->subscription_ends_at->format('Y-m-d'))
        ->toBe($originalEndsAt->copy()->addDays(14)->format('Y-m-d'));
});

it('ChangeOwnerTariffHandler меняет тариф владельца', function (): void {
    $tariff = Tariff::factory()->create(['title' => 'Premium']);
    $user = User::factory()->create();

    app(ChangeOwnerTariffHandler::class)->handle(new ChangeOwnerTariffCommand(
        ownerId: (string) $user->id,
        tariffId: (string) $tariff->id,
    ));

    $user->refresh();
    expect((string) $user->tariff_id)->toBe((string) $tariff->id);
});

it('ChangeOwnerTariffHandler сбрасывает тариф при null', function (): void {
    $tariff = Tariff::factory()->create();
    $user = User::factory()->withTariff($tariff)->create();

    app(ChangeOwnerTariffHandler::class)->handle(new ChangeOwnerTariffCommand(
        ownerId: (string) $user->id,
        tariffId: null,
    ));

    $user->refresh();
    expect($user->tariff_id)->toBeNull();
});

it('ChangeOwnerTariffHandler бросает TariffNotFound на несуществующем id', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(ChangeOwnerTariffHandler::class)->handle(
        new ChangeOwnerTariffCommand(
            ownerId: (string) $user->id,
            tariffId: '99999999-9999-9999-9999-999999999999',
        ),
    ))->toThrow(TariffNotFound::class);
});

it('DeleteOwner каскадно удаляет платежи владельца', function (): void {
    $tariff = Tariff::factory()->create();
    $user = User::factory()->withTariff($tariff)->create();
    PaymentTransaction::query()->create([
        'id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
        'user_id' => $user->id,
        'tariff_id' => $tariff->id,
        'amount' => 99000,
        'status' => 'pending',
    ]);

    app(DeleteOwnerHandler::class)->handle(
        new DeleteOwnerCommand(ownerId: (string) $user->id),
    );

    expect(PaymentTransaction::query()->where('user_id', $user->id)->exists())->toBeFalse();
});
