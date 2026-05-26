<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * Path к собранному SPA-shell ровно тот же, что используется в `OwnerSpaController`.
 * Базируется от `base_path()`, поэтому относительный `../dist/owner/...`.
 */
function ownerSpaIndexPath(): string
{
    return base_path('../dist/owner/index.html');
}

function withOwnerSpaBundle(string $html): void
{
    $path = ownerSpaIndexPath();
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $html);
}

function withoutOwnerSpaBundle(): void
{
    $path = ownerSpaIndexPath();
    if (File::exists($path)) {
        File::delete($path);
    }
}

beforeEach(function (): void {
    config(['guardreviews.domain' => 'otziv.space']);
    withoutOwnerSpaBundle();
});

afterEach(function (): void {
    withoutOwnerSpaBundle();
});

it('возвращает SPA-shell под валидным тенантом', function (): void {
    User::factory()->create(['subdomain_slug' => 'cafe']);

    withOwnerSpaBundle('<!doctype html><html><body>Owner SPA</body></html>');

    $this->withHeader('X-Tenant-Slug', 'cafe')
        ->get('/owner')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Owner SPA');
});

it('SPA fallback работает на любой подпуть', function (): void {
    User::factory()->create(['subdomain_slug' => 'cafe']);

    withOwnerSpaBundle('<!doctype html><html><body>Owner SPA</body></html>');

    $this->withHeader('X-Tenant-Slug', 'cafe')
        ->get('/owner/places/123/edit')
        ->assertOk()
        ->assertSee('Owner SPA');
});

it('без тенанта возвращает 404', function (): void {
    withOwnerSpaBundle('<!doctype html>noop');

    $this->get('/owner')->assertNotFound();
});

it('если SPA-бандл не собран — 404', function (): void {
    User::factory()->create(['subdomain_slug' => 'cafe']);

    withoutOwnerSpaBundle();

    $this->withHeader('X-Tenant-Slug', 'cafe')
        ->get('/owner')
        ->assertNotFound();
});

it('/api/owner/me без сессии отдаёт 401', function (): void {
    User::factory()->create(['subdomain_slug' => 'cafe']);

    $this->withHeader('X-Tenant-Slug', 'cafe')
        ->getJson('/api/owner/me')
        ->assertUnauthorized();
});
