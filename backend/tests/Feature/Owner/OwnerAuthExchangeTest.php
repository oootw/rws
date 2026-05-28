<?php

declare(strict_types=1);

use App\Models\OwnerLoginRequest as OwnerLoginRequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('обменивает корректный код на сессию и возвращает OwnerMeView', function (): void {
    $user = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
    ]);
    issueLoginRequest($user->id, '123456');

    $response = $this->postJson('/api/owner/auth/exchange', [
        'code' => '123456',
    ], tenantHeaders($user))->assertOk();

    $response->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.subdomain', 'cafe')
        ->assertJsonPath('data.telegram_connected', true);

    expect(OwnerLoginRequestModel::query()->where('code', '123456')->first()->consumed_at)->not->toBeNull();

    $this->getJson('/api/owner/me', tenantHeaders($user))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('отдаёт 422 login_code_invalid если кода не существует', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'cafe']);

    $this->postJson('/api/owner/auth/exchange', [
        'code' => '999999',
    ], tenantHeaders($user))
        ->assertStatus(422)
        ->assertJsonPath('code', 'login_code_invalid');
});

it('отдаёт 422 login_code_invalid если код истёк', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'cafe']);
    issueLoginRequest(
        ownerId: $user->id,
        code: '123456',
        now: new DateTimeImmutable('-1 hour'),
        ttlSeconds: 60,
    );

    $this->postJson('/api/owner/auth/exchange', [
        'code' => '123456',
    ], tenantHeaders($user))
        ->assertStatus(422)
        ->assertJsonPath('code', 'login_code_invalid');
});

it('запрещает обмен кода с чужого поддомена', function (): void {
    $owner = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
    ]);
    $intruder = User::factory()->create(['subdomain_slug' => 'bar']);
    issueLoginRequest($owner->id, '654321');

    $this->postJson('/api/owner/auth/exchange', [
        'code' => '654321',
    ], tenantHeaders($intruder))
        ->assertStatus(403)
        ->assertJsonPath('code', 'session_tenant_mismatch');
});

it('валидирует формат кода (только 6 цифр)', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'cafe']);

    $this->postJson('/api/owner/auth/exchange', [
        'code' => 'ABC',
    ], tenantHeaders($user))
        ->assertStatus(422);
});

it('/api/owner/me без сессии возвращает 401', function (): void {
    $user = User::factory()->create(['subdomain_slug' => 'cafe']);

    $this->getJson('/api/owner/me', tenantHeaders($user))->assertUnauthorized();
});

it('/api/owner/auth/logout инвалидирует сессию', function (): void {
    $user = User::factory()->create([
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
    ]);
    issueLoginRequest($user->id, '424242');

    $this->postJson('/api/owner/auth/exchange', ['code' => '424242'], tenantHeaders($user))->assertOk();

    $this->postJson('/api/owner/auth/logout', [], tenantHeaders($user))
        ->assertOk()
        ->assertJsonPath('data.logged_out', true);

    $this->getJson('/api/owner/me', tenantHeaders($user))->assertUnauthorized();
});
