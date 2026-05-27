<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('обновляет name/email/subdomain через PATCH /profile', function (): void {
    $owner = User::factory()->create([
        'name' => 'Старое имя',
        'email' => 'old@example.com',
        'subdomain_slug' => 'cafe',
        'telegram_id' => '1001',
    ]);
    loginAsOwner($owner);

    $this->patchJson(
        '/api/owner/profile',
        ['name' => 'Новое имя', 'email' => 'new@example.com', 'subdomain' => 'new-cafe'],
        tenantHeaders($owner),
    )
        ->assertOk()
        ->assertJsonPath('data.name', 'Новое имя')
        ->assertJsonPath('data.email', 'new@example.com')
        ->assertJsonPath('data.subdomain', 'new-cafe')
        ->assertJsonPath('data.telegram_connected', true);

    $owner->refresh();
    expect($owner->name)->toBe('Новое имя')
        ->and($owner->email)->toBe('new@example.com')
        ->and($owner->subdomain_slug)->toBe('new-cafe')
        ->and($owner->telegram_id)->toBe('1001');
});

it('не меняет subdomain если он совпадает с текущим', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->patchJson('/api/owner/profile', [
        'name' => $owner->name,
        'email' => $owner->email,
        'subdomain' => 'cafe',
    ], tenantHeaders($owner))->assertOk();
});

it('возвращает 422 при занятом subdomain', function (): void {
    User::factory()->create(['subdomain_slug' => 'taken']);
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->patchJson('/api/owner/profile', [
        'name' => $owner->name,
        'email' => $owner->email,
        'subdomain' => 'taken',
    ], tenantHeaders($owner))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['subdomain']);

    expect($owner->fresh()->subdomain_slug)->toBe('cafe');
});

it('валидирует email и формат subdomain', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $this->patchJson('/api/owner/profile', [
        'name' => $owner->name,
        'email' => 'not-an-email',
        'subdomain' => 'CAFE!!',
    ], tenantHeaders($owner))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'subdomain']);
});

it('выдаёт fresh magic-код для текущего привязанного Telegram', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);

    $response = $this->postJson('/api/owner/profile/telegram/issue-code', [], tenantHeaders($owner))
        ->assertOk()
        ->assertJsonStructure(['data' => ['code', 'expires_at']]);

    $code = (string) $response->json('data.code');
    expect($code)->toMatch('/^\d{6}$/');

    $stored = DB::table('owner_login_requests')
        ->where('owner_id', $owner->id)
        ->where('code', $code)
        ->whereNull('consumed_at')
        ->first();
    expect($stored)->not->toBeNull();
});

it('возвращает 422 если Telegram не привязан', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);
    loginAsOwner($owner);
    // Отвязываем уже после логина — у нас нет endpoint'а отвязки, отвязываем напрямую.
    $owner->forceFill(['telegram_id' => null])->save();

    $this->postJson('/api/owner/profile/telegram/issue-code', [], tenantHeaders($owner))
        ->assertStatus(422)
        ->assertJsonPath('code', 'owner_not_linked_to_telegram');
});

it('требует авторизации для PATCH /profile и issue-code', function (): void {
    $owner = User::factory()->create(['subdomain_slug' => 'cafe', 'telegram_id' => '1001']);

    $this->patchJson('/api/owner/profile', [
        'name' => 'X', 'email' => 'x@x.io', 'subdomain' => 'cafe',
    ], tenantHeaders($owner))->assertStatus(401);

    $this->postJson('/api/owner/profile/telegram/issue-code', [], tenantHeaders($owner))->assertStatus(401);
});
