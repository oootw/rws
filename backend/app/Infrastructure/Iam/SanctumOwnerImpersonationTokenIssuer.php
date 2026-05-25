<?php

declare(strict_types=1);

namespace App\Infrastructure\Iam;

use App\Application\Iam\IssueOwnerImpersonationToken\OwnerImpersonationTokenIssuer;
use App\Domain\Iam\OwnerId;
use App\Models\User as UserModel;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Адаптер порта OwnerImpersonationTokenIssuer на основе Laravel Sanctum.
 *
 * Имя токена — `impersonation:{ownerId}` (видно админу в админке как индикатор
 * выданных активных токенов); ability `impersonated` помечает сессию как
 * имперсонированную, чтобы ЛК владельца мог запретить опасные операции.
 *
 * Eloquent-модель User здесь намеренно — это Infrastructure-слой.
 */
final class SanctumOwnerImpersonationTokenIssuer implements OwnerImpersonationTokenIssuer
{
    public function issue(OwnerId $ownerId, DateTimeImmutable $expiresAt): array
    {
        $user = UserModel::query()->find($ownerId->value);

        if ($user === null) {
            // На уровне use case это уже проверено, но защита от гонок.
            throw new RuntimeException("Owner {$ownerId->value} disappeared between check and token issuance.");
        }

        $newAccessToken = $user->createToken(
            name: "impersonation:{$ownerId->value}",
            abilities: ['impersonated'],
            expiresAt: Carbon::instance($expiresAt),
        );

        return [
            'plain_text' => $newAccessToken->plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }
}
