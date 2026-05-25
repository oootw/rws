<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use App\Domain\Notifications\OwnerContact;
use DateTimeImmutable;

/**
 * Aggregate root контекста Iam: владелец сервиса.
 *
 * Что инкапсулирует:
 *  - контакты владельца (email, Telegram, MAX) и поддомен (адрес сайта);
 *  - подписку (Subscription VO с понятием активности и продления);
 *  - привязку к тарифу как ID (Tariff — отдельная сущность).
 *
 * Что НЕ делает:
 *  - не хранит и не считает точки/отзывы — это другие контексты;
 *  - не знает, как себя рисовать или валидироваться формами.
 */
final class Owner
{
    private function __construct(
        public readonly OwnerId $id,
        private string $name,
        private Email $email,
        private SubdomainSlug $subdomain,
        private ?TelegramId $telegramId,
        private ?string $maxId,
        private ?TariffId $tariffId,
        private Subscription $subscription,
    ) {}

    public static function register(
        OwnerId $id,
        string $name,
        Email $email,
        SubdomainSlug $subdomain,
        ?TelegramId $telegramId,
        ?TariffId $tariffId,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            subdomain: $subdomain,
            telegramId: $telegramId,
            maxId: null,
            tariffId: $tariffId,
            subscription: Subscription::none(),
        );
    }

    public static function restore(
        OwnerId $id,
        string $name,
        Email $email,
        SubdomainSlug $subdomain,
        ?TelegramId $telegramId,
        ?string $maxId,
        ?TariffId $tariffId,
        Subscription $subscription,
    ): self {
        return new self($id, $name, $email, $subdomain, $telegramId, $maxId, $tariffId, $subscription);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function subdomain(): SubdomainSlug
    {
        return $this->subdomain;
    }

    public function telegramId(): ?TelegramId
    {
        return $this->telegramId;
    }

    public function maxId(): ?string
    {
        return $this->maxId;
    }

    public function tariffId(): ?TariffId
    {
        return $this->tariffId;
    }

    public function subscription(): Subscription
    {
        return $this->subscription;
    }

    public function hasActiveSubscriptionAt(DateTimeImmutable $moment): bool
    {
        return $this->subscription->isActiveAt($moment);
    }

    public function extendSubscription(int $days, DateTimeImmutable $now): void
    {
        $this->subscription = $this->subscription->extend($days, $now);
    }

    /**
     * Меняет профильные поля владельца (используется админкой и будущим ЛК).
     * Доменные инварианты проверяют VO: Email, SubdomainSlug, TelegramId.
     */
    public function changeProfile(
        string $name,
        Email $email,
        SubdomainSlug $subdomain,
        ?TelegramId $telegramId,
        ?TariffId $tariffId,
    ): void {
        $this->name = $name;
        $this->email = $email;
        $this->subdomain = $subdomain;
        $this->telegramId = $telegramId;
        $this->tariffId = $tariffId;
    }

    /**
     * Прямой override срока подписки админом. null = сбросить подписку.
     * В отличие от extendSubscription() не накапливает срок: ставит точную дату.
     */
    public function overrideSubscription(?DateTimeImmutable $endsAt): void
    {
        $this->subscription = new Subscription($endsAt);
    }

    public function changeTariff(?TariffId $tariffId): void
    {
        $this->tariffId = $tariffId;
    }

    public function subdomainEquals(SubdomainSlug $candidate): bool
    {
        return $this->subdomain->value === $candidate->value;
    }

    public function asNotificationContact(): OwnerContact
    {
        return new OwnerContact(
            telegramId: $this->telegramId?->value,
            maxId: $this->maxId,
            email: $this->email->value,
        );
    }

    public function scanBaseUrl(string $domain): string
    {
        return "https://{$this->subdomain->value}.{$domain}";
    }
}
