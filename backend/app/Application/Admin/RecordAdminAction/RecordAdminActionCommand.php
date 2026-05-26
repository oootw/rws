<?php

declare(strict_types=1);

namespace App\Application\Admin\RecordAdminAction;

final readonly class RecordAdminActionCommand
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        public string $adminEmail,
        public string $action,
        public ?string $resource = null,
        public ?string $recordId = null,
        public ?array $payload = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {}
}
