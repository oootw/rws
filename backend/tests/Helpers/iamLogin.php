<?php

declare(strict_types=1);

use App\Domain\Iam\OwnerLoginRequest;
use App\Domain\Iam\OwnerLoginRequestId;
use App\Domain\Iam\OwnerLoginRequestIdGenerator;
use App\Domain\Iam\OwnerLoginRequestRepository;
use App\Domain\Shared\Clock\Clock;

/**
 * @param  list<OwnerLoginRequest>  $requests
 */
function fakeOwnerLoginRequestRepository(array $requests = [], ?Clock $clock = null): OwnerLoginRequestRepository
{
    return new class($requests, $clock) implements OwnerLoginRequestRepository
    {
        /** @var list<OwnerLoginRequest> */
        public array $requests;

        public function __construct(array $requests, private ?Clock $clock)
        {
            $this->requests = $requests;
        }

        public function save(OwnerLoginRequest $request): void
        {
            foreach ($this->requests as $index => $stored) {
                if ($stored->id->equals($request->id)) {
                    $this->requests[$index] = $request;

                    return;
                }
            }

            $this->requests[] = $request;
        }

        public function findActiveByCode(string $code): ?OwnerLoginRequest
        {
            $now = $this->clock?->now();

            foreach ($this->requests as $request) {
                if ($request->code !== $code) {
                    continue;
                }
                if ($request->isConsumed()) {
                    continue;
                }
                if ($now !== null && $request->isExpiredAt($now)) {
                    continue;
                }

                return $request;
            }

            return null;
        }

        public function findById(OwnerLoginRequestId $id): ?OwnerLoginRequest
        {
            foreach ($this->requests as $request) {
                if ($request->id->equals($id)) {
                    return $request;
                }
            }

            return null;
        }
    };
}

function fakeOwnerLoginRequestIdGenerator(string $value): OwnerLoginRequestIdGenerator
{
    return new class($value) implements OwnerLoginRequestIdGenerator
    {
        public function __construct(private string $value) {}

        public function next(): OwnerLoginRequestId
        {
            return new OwnerLoginRequestId($this->value);
        }
    };
}
