<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Application\Jobs\FailedJobsActions;
use Illuminate\Contracts\Console\Kernel as ArtisanKernel;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Адаптер портa FailedJobsActions поверх стандартных Artisan-команд Laravel:
 *  - retry  → `queue:retry {uuid}` (он сам перекладывает в очередь и чистит failed_jobs);
 *  - delete → прямой DELETE по uuid (быстро и без побочных эффектов);
 *  - prune  → DELETE по failed_at < now() - X дней.
 *
 * Один вызов = одно действие. Логика «применить ко всем выбранным»
 * — на стороне Filament-страницы (она и так получает Collection).
 */
final readonly class LaravelFailedJobsActions implements FailedJobsActions
{
    public function __construct(
        private ArtisanKernel $artisan,
        private ConnectionResolverInterface $connections,
    ) {}

    public function retry(string $uuid): bool
    {
        $exitCode = $this->artisan->call('queue:retry', ['id' => [$uuid]]);

        return $exitCode === 0;
    }

    public function delete(string $uuid): bool
    {
        $affected = $this->connections->connection()
            ->table('failed_jobs')
            ->where('uuid', $uuid)
            ->delete();

        return $affected > 0;
    }

    public function prune(int $olderThanDays): int
    {
        $query = $this->connections->connection()->table('failed_jobs');

        if ($olderThanDays > 0) {
            $cutoff = now()->subDays($olderThanDays);
            $query->where('failed_at', '<', $cutoff);
        }

        return (int) $query->delete();
    }
}
