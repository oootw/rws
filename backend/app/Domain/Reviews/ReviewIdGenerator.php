<?php

declare(strict_types=1);

namespace App\Domain\Reviews;

interface ReviewIdGenerator
{
    public function next(): ReviewId;
}
