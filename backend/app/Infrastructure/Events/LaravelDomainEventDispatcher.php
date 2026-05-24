<?php

declare(strict_types=1);

namespace App\Infrastructure\Events;

use App\Application\Shared\Events\DomainEventDispatcher;
use App\Domain\Shared\Events\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LaravelDomainEventDispatcher implements DomainEventDispatcher
{
    public function __construct(
        private Dispatcher $events,
    ) {}

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            assert($event instanceof DomainEvent);
            $this->events->dispatch($event);
        }
    }
}
