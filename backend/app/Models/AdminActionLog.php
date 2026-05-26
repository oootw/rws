<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'admin_email',
    'action',
    'resource',
    'record_id',
    'payload',
    'ip',
    'user_agent',
    'created_at',
])]
class AdminActionLog extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'admin_action_logs';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
