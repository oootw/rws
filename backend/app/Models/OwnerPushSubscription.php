<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'owner_id',
    'endpoint',
    'p256dh',
    'auth',
    'user_agent',
    'created_at',
    'last_seen_at',
])]
class OwnerPushSubscription extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'owner_push_subscriptions';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
