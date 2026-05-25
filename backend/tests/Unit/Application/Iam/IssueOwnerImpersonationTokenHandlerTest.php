<?php

declare(strict_types=1);

use App\Application\Iam\Exceptions\TenantNotFound;
use App\Application\Iam\IssueOwnerImpersonationToken\IssueOwnerImpersonationTokenCommand;
use App\Application\Iam\IssueOwnerImpersonationToken\IssueOwnerImpersonationTokenHandler;
use App\Application\Iam\IssueOwnerImpersonationToken\OwnerImpersonationTokenIssuer;
use App\Domain\Iam\OwnerId;

function fakeImpersonationIssuer(): OwnerImpersonationTokenIssuer
{
    return new class implements OwnerImpersonationTokenIssuer
    {
        /** @var list<array{owner_id: string, expires_at: DateTimeImmutable}> */
        public array $issuedFor = [];

        public function issue(OwnerId $ownerId, DateTimeImmutable $expiresAt): array
        {
            $this->issuedFor[] = ['owner_id' => $ownerId->value, 'expires_at' => $expiresAt];

            return [
                'plain_text' => "test-token-{$ownerId->value}",
                'expires_at' => $expiresAt,
            ];
        }
    };
}

it('выпускает токен имперсонации с дефолтным TTL 15 минут', function (): void {
    $owner = restoredOwner();
    $owners = fakeOwnerRepository([$owner]);
    $issuer = fakeImpersonationIssuer();
    $clock = frozenClockAt('2026-06-01T10:00:00Z');

    $result = (new IssueOwnerImpersonationTokenHandler($owners, $issuer, $clock))->handle(
        new IssueOwnerImpersonationTokenCommand(ownerId: $owner->id->value),
    );

    expect($result->plainTextToken)->toBe("test-token-{$owner->id->value}")
        ->and($result->expiresAt->format('H:i'))->toBe('10:15')
        ->and($issuer->issuedFor)->toHaveCount(1)
        ->and($issuer->issuedFor[0]['owner_id'])->toBe($owner->id->value);
});

it('использует кастомный TTL', function (): void {
    $owner = restoredOwner();

    $result = (new IssueOwnerImpersonationTokenHandler(
        fakeOwnerRepository([$owner]),
        fakeImpersonationIssuer(),
        frozenClockAt('2026-06-01T10:00:00Z'),
    ))->handle(new IssueOwnerImpersonationTokenCommand(
        ownerId: $owner->id->value,
        ttlMinutes: 60,
    ));

    expect($result->expiresAt->format('H:i'))->toBe('11:00');
});

it('бросает TenantNotFound для несуществующего владельца', function (): void {
    (new IssueOwnerImpersonationTokenHandler(
        fakeOwnerRepository(),
        fakeImpersonationIssuer(),
        frozenClockAt('2026-06-01T10:00:00Z'),
    ))->handle(new IssueOwnerImpersonationTokenCommand(
        ownerId: '00000000-0000-0000-0000-000000000000',
    ));
})->throws(TenantNotFound::class);
