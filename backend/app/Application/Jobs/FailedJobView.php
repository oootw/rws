<?php

declare(strict_types=1);

namespace App\Application\Jobs;

use DateTimeImmutable;

/**
 * Read-model одной строки `failed_jobs` для админ-просмотра.
 * Намеренно не раскрываем сериализованный payload — admin его не правит,
 * а лишь решает «retry / delete».
 */
final readonly class FailedJobView
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $connection,
        public string $queue,
        public string $jobClass,
        public string $exceptionFirstLine,
        public DateTimeImmutable $failedAt,
    ) {}
}
