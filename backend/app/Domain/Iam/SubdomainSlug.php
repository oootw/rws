<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

/**
 * Поддомен владельца внутри основного домена сервиса.
 *
 * Доменное правило: формат — латиница/цифры/дефис, 3–32 символа,
 * не из списка зарезервированных. Уникальность проверяется не здесь
 * (это уже про хранилище — см. SubdomainSlug + OwnerRepository).
 */
final readonly class SubdomainSlug
{
    private const PATTERN = '/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])?$/';

    /** @var list<string> */
    private const RESERVED = ['www', 'api', 'admin', 'staging', 'privacy', 'mail', 'ftp'];

    public function __construct(public string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException('Адрес должен содержать 3–32 символа: латиница, цифры и дефис.');
        }

        if (in_array($value, self::RESERVED, true)) {
            throw new InvalidArgumentException('Этот адрес зарезервирован.');
        }
    }

    public static function isReserved(string $value): bool
    {
        return in_array($value, self::RESERVED, true);
    }
}
