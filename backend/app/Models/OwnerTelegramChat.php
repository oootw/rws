<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'owner_id',
    'chat_id',
    'title',
    'linked_at',
])]
class OwnerTelegramChat extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'owner_telegram_chats';

    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
        ];
    }
}
