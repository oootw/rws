<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Tinkoff;

use Illuminate\Contracts\Config\Repository as Config;

final readonly class TinkoffConfig
{
    public function __construct(private Config $config) {}

    public function terminalKey(): ?string
    {
        $value = $this->config->get('guardreviews.tinkoff.terminal_key');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function secretKey(): ?string
    {
        $value = $this->config->get('guardreviews.tinkoff.secret_key');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function apiUrl(): string
    {
        return rtrim((string) $this->config->get('guardreviews.tinkoff.api_url'), '/');
    }

    public function notificationUrl(): string
    {
        return (string) $this->config->get('guardreviews.tinkoff.notification_url');
    }

    public function successUrl(): string
    {
        return (string) $this->config->get('guardreviews.tinkoff.success_url');
    }

    public function failUrl(): string
    {
        return (string) $this->config->get('guardreviews.tinkoff.fail_url');
    }
}
