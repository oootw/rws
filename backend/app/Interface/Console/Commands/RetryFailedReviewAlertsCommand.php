<?php

declare(strict_types=1);

namespace App\Interface\Console\Commands;

use App\Jobs\SendNegativeReviewAlert;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Перезапускает все упавшие алерты о негативных отзывах
 * (job-класс SendNegativeReviewAlert) из таблицы `failed_jobs`.
 *
 * Зачем: основной канал (Telegram-прокси) может на час упасть, накопить
 * пачку failed jobs — после восстановления одной командой возвращаем их в очередь.
 *
 * Использование:
 *   php artisan reviews:retry-failed-alerts        # перезапустить все
 *   php artisan reviews:retry-failed-alerts --list # только показать список
 *   php artisan reviews:retry-failed-alerts --purge # удалить из failed_jobs (не запускать)
 */
final class RetryFailedReviewAlertsCommand extends Command
{
    protected $signature = 'reviews:retry-failed-alerts
        {--list  : Только показать список упавших алертов}
        {--purge : Удалить упавшие алерты из failed_jobs без перезапуска}';

    protected $description = 'Перезапускает упавшие уведомления о негативных отзывах из failed_jobs';

    public function handle(
        ConnectionResolverInterface $connections,
        QueueFactory $queue,
    ): int {
        $jobClass = SendNegativeReviewAlert::class;
        $matcher = '%'.str_replace('\\', '\\\\', $jobClass).'%';

        $rows = $connections->connection()
            ->table('failed_jobs')
            ->where('payload', 'like', $matcher)
            ->orderBy('failed_at')
            ->get(['id', 'uuid', 'queue', 'connection', 'payload', 'failed_at']);

        if ($rows->isEmpty()) {
            $this->info('Упавших алертов нет.');

            return self::SUCCESS;
        }

        $this->info("Найдено упавших алертов: {$rows->count()}");

        foreach ($rows as $row) {
            $payload = json_decode((string) $row->payload, true);
            $reviewId = $this->extractReviewId($payload);
            $this->line(sprintf(
                '  • uuid=%s  reviewId=%s  upal=%s  queue=%s',
                $row->uuid,
                $reviewId ?? '<unknown>',
                $row->failed_at,
                $row->queue,
            ));
        }

        if ($this->option('list')) {
            return self::SUCCESS;
        }

        if ($this->option('purge')) {
            if (! $this->confirm('Удалить эти записи из failed_jobs без перезапуска?')) {
                return self::SUCCESS;
            }

            $connections->connection()
                ->table('failed_jobs')
                ->whereIn('id', $rows->pluck('id'))
                ->delete();

            $this->info("Удалено: {$rows->count()}.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Перезапустить {$rows->count()} алертов?", true)) {
            return self::SUCCESS;
        }

        $requeued = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $payload = json_decode((string) $row->payload, true);
            $reviewId = $this->extractReviewId($payload);

            if ($reviewId === null) {
                $this->warn("  ⨯ uuid={$row->uuid} — не смог распарсить reviewId, пропускаю");
                $skipped++;

                continue;
            }

            $queue->connection((string) $row->connection)->pushOn(
                (string) ($row->queue !== '' ? $row->queue : 'default'),
                new SendNegativeReviewAlert($reviewId),
            );

            $connections->connection()
                ->table('failed_jobs')
                ->where('id', $row->id)
                ->delete();

            $requeued++;
        }

        $this->info("Перезапущено: {$requeued}. Пропущено: {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractReviewId(?array $payload): ?string
    {
        $data = $payload['data']['command'] ?? null;

        if (! is_string($data)) {
            return null;
        }

        $job = @unserialize($data, ['allowed_classes' => [SendNegativeReviewAlert::class]]);

        return $job instanceof SendNegativeReviewAlert ? $job->reviewId : null;
    }
}
