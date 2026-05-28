<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Iam\Exceptions\PushSubscriptionNotFound;
use App\Application\Iam\RegisterPushSubscription\RegisterPushSubscriptionCommand;
use App\Application\Iam\RegisterPushSubscription\RegisterPushSubscriptionHandler;
use App\Application\Iam\UnregisterPushSubscription\UnregisterPushSubscriptionCommand;
use App\Application\Iam\UnregisterPushSubscription\UnregisterPushSubscriptionHandler;
use App\Domain\Iam\OwnerId;
use App\Domain\Iam\PushSubscriptionRepository;
use App\Enums\ApiErrorCode;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use App\Interface\Http\Requests\Owner\SubscribePushRequest;
use App\Interface\Http\Requests\Owner\UnsubscribePushRequest;
use App\Support\ApiResponse;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

final readonly class OwnerPushController
{
    public function __construct(
        private RegisterPushSubscriptionHandler $register,
        private UnregisterPushSubscriptionHandler $unregister,
        private PushSubscriptionRepository $subscriptions,
        private Config $config,
    ) {}

    public function config(Request $request): JsonResponse
    {
        $publicKey = (string) $this->config->get('services.webpush.public_key', '');
        $privateKey = (string) $this->config->get('services.webpush.private_key', '');
        $subject = (string) $this->config->get('services.webpush.subject', '');

        $enabled = $publicKey !== '' && $privateKey !== '' && $subject !== '';

        // ownerId зашит в сессии — request обращается к ней по необходимости.
        unset($request);

        return response()->json([
            'data' => [
                'vapid_public_key' => $publicKey,
                'enabled' => $enabled,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $data = array_map(
            static fn ($s): array => [
                'id' => $s->id->value,
                'endpoint' => $s->endpoint->value,
                'user_agent' => $s->userAgent,
                'created_at' => $s->createdAt->format(DATE_ATOM),
                'last_seen_at' => $s->lastSeenAt()?->format(DATE_ATOM),
            ],
            $this->subscriptions->listByOwner(new OwnerId($ownerId->value)),
        );

        return response()->json(['data' => $data]);
    }

    public function subscribe(SubscribePushRequest $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        try {
            $this->register->handle(new RegisterPushSubscriptionCommand(
                ownerId: $ownerId->value,
                endpoint: (string) $request->input('endpoint'),
                p256dh: (string) $request->input('keys.p256dh'),
                auth: (string) $request->input('keys.auth'),
                userAgent: $request->input('user_agent') !== null
                    ? (string) $request->input('user_agent')
                    : null,
            ));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['ok' => true]], 201);
    }

    public function unsubscribe(UnsubscribePushRequest $request): Response|JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        try {
            $this->unregister->handle(new UnregisterPushSubscriptionCommand(
                ownerId: $ownerId->value,
                endpoint: (string) $request->input('endpoint'),
            ));
        } catch (PushSubscriptionNotFound) {
            return ApiResponse::error(ApiErrorCode::PushSubscriptionNotFound, 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }
}
