<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\Channels\WebPushClient;
use App\Application\Notifications\OwnerNotification;
use App\Domain\Iam\PushSubscriptionEndpoint;
use App\Domain\Iam\PushSubscriptionRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Доставка через Web Push: по одной попытке на каждое устройство владельца.
 *
 * Контракт «без throw» сохранён:
 *  - сетевые и валидационные ошибки одной подписки логируются и не валят цикл;
 *  - gone (404/410) → подписка удаляется из БД;
 *  - канал считается «доставившим», если хотя бы одна подписка приняла пуш.
 *    Если все мертвы — delivered=false, MultiChannelOwnerNotifier перейдёт
 *    к следующему instant-каналу или fallback (email).
 */
final readonly class WebPushNotificationChannel implements NotificationChannel
{
    public function __construct(
        private WebPushClient $client,
        private PushSubscriptionRepository $subscriptions,
        private Config $config,
        private LoggerInterface $logger,
    ) {}

    public function supports(OwnerNotification $notification): bool
    {
        return $this->isVapidConfigured()
            && $notification->contact->hasPushSubscriptions();
    }

    public function deliver(OwnerNotification $notification): void
    {
        $payload = $this->buildPayload($notification);
        $delivered = false;
        $hadLiveSubscriptions = false;

        foreach ($notification->contact->pushSubscriptions as $view) {
            try {
                $result = $this->client->send($view, $payload);
            } catch (Throwable $e) {
                $this->logger->warning('Web Push send failed', [
                    'endpoint' => $view->endpoint,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if ($result->gone) {
                $this->subscriptions->markGone(new PushSubscriptionEndpoint($view->endpoint));

                continue;
            }

            $hadLiveSubscriptions = true;

            if ($result->delivered) {
                $delivered = true;
            }
        }

        // Контракт NotificationChannel: throw только если были живые подписки,
        // но ни одну из них доставить не удалось — это «настоящая ошибка»
        // канала, и MultiChannelOwnerNotifier должен это услышать.
        if (! $delivered && $hadLiveSubscriptions) {
            throw new WebPushDeliveryFailed;
        }
    }

    private function isVapidConfigured(): bool
    {
        return is_string($this->config->get('services.webpush.public_key'))
            && (string) $this->config->get('services.webpush.public_key') !== ''
            && is_string($this->config->get('services.webpush.private_key'))
            && (string) $this->config->get('services.webpush.private_key') !== ''
            && is_string($this->config->get('services.webpush.subject'))
            && (string) $this->config->get('services.webpush.subject') !== '';
    }

    private function buildPayload(OwnerNotification $notification): string
    {
        return (string) json_encode([
            'title' => $notification->emailSubject,
            'body' => $notification->text,
            'url' => $notification->targetUrl ?? '/owner',
            'tag' => $notification->kind,
            'kind' => $notification->kind,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
