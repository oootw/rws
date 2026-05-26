<?php

declare(strict_types=1);

namespace App\Domain\Admin;

use DateTimeImmutable;

/**
 * Запись аудита действия, совершённого админом в Filament-панели.
 * Простая иммутабельная запись (как ActionLog), а не агрегат с поведением.
 */
final readonly class AdminActionLog
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public AdminActionLogId $id,
        public string $adminEmail,
        public string $action,
        public ?string $resource,
        public ?string $recordId,
        public ?array $payload,
        public ?string $ip,
        public ?string $userAgent,
        public DateTimeImmutable $recordedAt,
    ) {}
}
