<?php

declare(strict_types=1);

namespace App\Application\Jobs;

/**
 * Порт операций над failed_jobs: retry/delete/prune.
 * Реализация — Eloquent + Laravel queue API.
 *
 * Возвращаемые значения — int-счётчики, чтобы админ-страница
 * могла отрапортовать «перезапущено / удалено: N».
 */
interface FailedJobsActions
{
    public function retry(string $uuid): bool;

    public function delete(string $uuid): bool;

    /**
     * Удаляет все записи старше указанного количества дней.
     * Если $olderThanDays = 0 — удаляет все.
     */
    public function prune(int $olderThanDays): int;
}
