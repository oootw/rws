<?php

declare(strict_types=1);

namespace App\Application\Shared\Events;

use App\Domain\Shared\Events\DomainEvent;

interface DomainEventDispatcher
{
    /**
     * @param  iterable<DomainEvent>  $events
     */
    public function dispatchAll(iterable $events): void;
}
