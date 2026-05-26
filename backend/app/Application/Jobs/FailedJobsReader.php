<?php

declare(strict_types=1);

namespace App\Application\Jobs;

/**
 * Read-model порт для админ-просмотра очереди failed_jobs.
 *
 * Реализация знает про конкретное хранилище (Laravel `failed_jobs` table);
 * Filament-страница работает только с DTO.
 */
interface FailedJobsReader
{
    /**
     * @return list<FailedJobView>
     */
    public function all(int $limit = 200): array;

    public function count(): int;
}
