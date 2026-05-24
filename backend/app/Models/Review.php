<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'place_id',
    'stars',
    'contact',
    'text',
    'status',
    'ip_hash',
])]
class Review extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'status' => ReviewStatus::class,
        ];
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
