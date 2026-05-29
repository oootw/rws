<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'owner_id',
    'token',
    'expires_at',
    'consumed_at',
    'created_at',
])]
class OwnerChatLinkToken extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'owner_chat_link_tokens';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
