<?php

declare(strict_types=1);

use App\Jobs\SendNegativeReviewAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function failedReviewAlertJobPayload(string $reviewId): string
{
    $job = new SendNegativeReviewAlert($reviewId);

    return json_encode([
        'uuid' => (string) Str::uuid(),
        'displayName' => SendNegativeReviewAlert::class,
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'maxTries' => 10,
        'data' => [
            'commandName' => SendNegativeReviewAlert::class,
            'command' => serialize($job),
        ],
    ], JSON_THROW_ON_ERROR);
}

function insertFailedReviewAlertJob(
    string $reviewId,
    string $connection = 'database',
    string $queue = 'default',
): int {
    return (int) DB::table('failed_jobs')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'connection' => $connection,
        'queue' => $queue,
        'payload' => failedReviewAlertJobPayload($reviewId),
        'exception' => 'Test exception',
        'failed_at' => now(),
    ]);
}
