<?php

declare(strict_types=1);

namespace App\Application\Iam\ExchangeOwnerLoginCode;

use App\Application\Iam\Exceptions\LoginCodeNotFound;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Shared\Clock\Clock;

/**
 * Use case: SPA обменивает 6-значный код на сессию.
 * Возвращает OwnerId — Interface-слой делает `Auth::loginUsingId`.
 *
 * Доменные исключения LoginCodeExpired / LoginCodeAlreadyConsumed
 * выбрасываются агрегатом OwnerLoginRequest::consume() — здесь не ловим,
 * чтобы HTTP-слой смог перевести их в 422 с понятным сообщением.
 */
final readonly class ExchangeOwnerLoginCodeHandler
{
    public function __construct(
        private OwnerLoginRequestRepository $requests,
        private Clock $clock,
    ) {}

    public function handle(ExchangeOwnerLoginCodeCommand $command): OwnerId
    {
        $request = $this->requests->findActiveByCode($command->code);

        if ($request === null) {
            throw new LoginCodeNotFound;
        }

        $request->consume($this->clock->now());

        $this->requests->save($request);

        return $request->ownerId;
    }
}
