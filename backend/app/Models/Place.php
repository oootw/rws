<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistence-модель точки. Доменное поведение живёт в App\Domain\Places\Place;
 * этот класс знает только формат БД и Eloquent-отношения, которые ещё нужны
 * другим контекстам (Iam, Telegram), пока их репозитории не переехали.
 */
#[Fillable([
    'user_id',
    'title',
    'platforms',
    'background_image_url',
    'is_active',
])]
class Place extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(ActionLog::class);
    }
}
