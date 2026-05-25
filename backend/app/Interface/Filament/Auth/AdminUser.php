<?php

declare(strict_types=1);

namespace App\Interface\Filament\Auth;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * In-memory представление единственного супер-админа.
 *
 * Не хранится в БД, не имеет связей. Создаётся EnvUserProvider'ом
 * из config('guardreviews.admin'). Реализует FilamentUser, чтобы
 * Filament пускал в панель в любом окружении.
 *
 * Наследуем GenericUser, чтобы получить из коробки реализацию
 * Authenticatable (id/auth_password/remember_token/...).
 */
final class AdminUser extends GenericUser implements Authenticatable, FilamentUser, HasAvatar, HasName
{
    /**
     * @param  array{id: int|string, email: string, name: string, password: string}  $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Единственный админ имеет доступ ко всем панелям приложения.
        return $panel->getId() === 'admin';
    }

    /**
     * Filament/Blade обращаются к свойствам — отдаём из массива атрибутов.
     */
    public function getFilamentName(): string
    {
        return (string) ($this->attributes['name'] ?? 'Admin');
    }

    public function getEmail(): string
    {
        return (string) ($this->attributes['email'] ?? '');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        // Используем default avatar provider Filament (ui-avatars).
        return null;
    }

    /**
     * Filament/Livewire местами зовут Eloquent-API даже на Authenticatable
     * (например, в notifications.blade.php). Мы не Eloquent, но идентификатор
     * у нас стабильный — отдаём его.
     */
    public function getKey(): string
    {
        return (string) ($this->attributes['id'] ?? 'admin');
    }

    /**
     * Eloquent-совместимый геттер: некоторые Blade-вью Filament читают
     * атрибуты пользователя через getAttributeValue('name'|'avatar_url'|...).
     * Возвращаем напрямую из массива, без аксессоров.
     */
    public function getAttributeValue(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Remember-me отключён намеренно (EnvUserProvider тоже не сохраняет токен).
     * GenericUser кидает «Undefined array key» если remember_token не в массиве —
     * перекрываем, чтобы AuthenticateSession middleware был happy.
     */
    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // No-op: см. комментарий выше.
    }
}
