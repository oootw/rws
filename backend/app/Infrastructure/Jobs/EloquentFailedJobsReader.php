<?php

declare(strict_types=1);

namespace App\Infrastructure\Jobs;

use App\Application\Jobs\FailedJobsReader;
use App\Application\Jobs\FailedJobView;
use DateTimeImmutable;
use Illuminate\Database\ConnectionResolverInterface;

/**
 * Читает таблицу `failed_jobs` напрямую (Laravel её администрирует, но
 * Eloquent-модели для неё нет — это запись низкоуровневой системы очередей).
 *
 * Один SQL за вызов, без сериализации payload в память: парсим только
 * displayName и первую строку exception для админ-обзора.
 */
final readonly class EloquentFailedJobsReader implements FailedJobsReader
{
    public function __construct(
        private ConnectionResolverInterface $connections,
    ) {}

    public function all(int $limit = 200): array
    {
        $rows = $this->connections->connection()
            ->table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get(['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at']);

        return $rows
            ->map(fn (object $row): FailedJobView => $this->toView($row))
            ->values()
            ->all();
    }

    public function count(): int
    {
        return (int) $this->connections->connection()->table('failed_jobs')->count();
    }

    private function toView(object $row): FailedJobView
    {
        $payload = json_decode((string) $row->payload, true);
        $jobClass = is_array($payload) && isset($payload['displayName']) && is_string($payload['displayName'])
            ? $payload['displayName']
            : 'unknown';

        $exception = (string) ($row->exception ?? '');
        $firstLine = $exception === '' ? '' : explode("\n", $exception, 2)[0];

        return new FailedJobView(
            id: (int) $row->id,
            uuid: (string) $row->uuid,
            connection: (string) ($row->connection ?? ''),
            queue: (string) ($row->queue ?? ''),
            jobClass: $jobClass,
            exceptionFirstLine: $firstLine,
            failedAt: new DateTimeImmutable((string) $row->failed_at),
        );
    }
}
