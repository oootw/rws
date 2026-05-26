<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'owner_id',
    'telegram_id',
    'code',
    'expires_at',
    'consumed_at',
    'created_at',
])]
class OwnerLoginRequest extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'owner_login_requests';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
