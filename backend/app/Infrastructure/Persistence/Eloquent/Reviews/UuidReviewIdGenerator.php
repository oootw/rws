<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Reviews;

use App\Domain\Reviews\ReviewId;
use App\Domain\Reviews\ReviewIdGenerator;
use Illuminate\Support\Str;

final class UuidReviewIdGenerator implements ReviewIdGenerator
{
    public function next(): ReviewId
    {
        return new ReviewId((string) Str::uuid());
    }
}
