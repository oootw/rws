<?php

declare(strict_types=1);

namespace App\Application\Notifications\BuildOwnerContact;

final readonly class BuildOwnerContactQuery
{
    public function __construct(public string $ownerId) {}
}
