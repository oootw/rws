<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Transactions;

use App\Application\Shared\Transactions\TransactionRunner;
use Closure;
use Illuminate\Database\ConnectionResolverInterface;

final readonly class EloquentTransactionRunner implements TransactionRunner
{
    public function __construct(
        private ConnectionResolverInterface $connections,
    ) {}

    public function run(Closure $callback): mixed
    {
        return $this->connections->connection()->transaction($callback);
    }
}
