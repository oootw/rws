<?php

declare(strict_types=1);

namespace App\Domain\Shared\Identity;

/**
 * Уже захэшированный отпечаток IP-адреса посетителя.
 * Хеширование — забота инфраструктуры/интерфейса; домен принимает результат.
 */
final readonly class IpHash
{
    private function __construct(public string $value) {}

    public static function fromHashed(?string $hashed): ?self
    {
        if ($hashed === null || $hashed === '') {
            return null;
        }

        return new self($hashed);
    }
}
