<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Заглушка `GET /api/owner/me`. Возвращает 401 в Фазе 0,
 * пока полная аутентификация через Telegram magic-code не подключена (Фаза 1).
 * SPA уже умеет обрабатывать 401 и редиректить на /login.
 */
final class OwnerMeController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user('owner');

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subdomain' => $user->subdomain_slug,
            ],
        ]);
    }
}
