<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Push;

use App\Application\Notifications\Channels\WebPushClient;
use App\Application\Notifications\Channels\WebPushSendResult;
use App\Domain\Notifications\PushSubscriptionView;
use Illuminate\Contracts\Config\Repository as Config;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Адаптер порта WebPushClient на minishlink/web-push.
 *
 * VAPID-конфиг читается лениво из config('services.webpush.*'),
 * чтобы не падать на старте, когда ключей ещё нет (`supports()` канала
 * вернёт false при отсутствии конфигурации).
 */
final readonly class MinishlinkWebPushClient implements WebPushClient
{
    public function __construct(private Config $config) {}

    public function send(PushSubscriptionView $subscription, string $payload): WebPushSendResult
    {
        $vapid = $this->vapidAuth();

        if ($vapid === null) {
            return WebPushSendResult::failed();
        }

        try {
            $report = (new WebPush(['VAPID' => $vapid]))->sendOneNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->p256dh,
                        'auth' => $subscription->auth,
                    ],
                ]),
                $payload,
            );
        } catch (Throwable) {
            return WebPushSendResult::failed();
        }

        if ($report->isSuccess()) {
            return WebPushSendResult::delivered();
        }

        return $report->isSubscriptionExpired()
            ? WebPushSendResult::gone()
            : WebPushSendResult::failed();
    }

    /**
     * @return array{subject: string, publicKey: string, privateKey: string}|null
     */
    private function vapidAuth(): ?array
    {
        $publicKey = $this->config->get('services.webpush.public_key');
        $privateKey = $this->config->get('services.webpush.private_key');
        $subject = $this->config->get('services.webpush.subject');

        if (! is_string($publicKey) || $publicKey === ''
            || ! is_string($privateKey) || $privateKey === ''
            || ! is_string($subject) || $subject === '') {
            return null;
        }

        return [
            'subject' => $subject,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }
}
