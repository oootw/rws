<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner\Support;

use App\Domain\Iam\OwnerId;
use App\Models\User;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Достаёт OwnerId из текущего request. Контроллеры под `auth:owner`
 * гарантированно имеют залогиненного user'а — null трактуем как ошибку
 * (middleware пропустил, чего быть не должно).
 */
final readonly class CurrentOwnerId
{
    public static function fromRequest(Request $request): OwnerId
    {
        /** @var User|null $user */
        $user = $request->user('owner');

        if ($user === null) {
            throw new RuntimeException('Owner guard returned no user — auth middleware missing on route.');
        }

        return new OwnerId((string) $user->id);
    }
}
