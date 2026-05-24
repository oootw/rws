<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Conversations;

use App\Application\Iam\Exceptions\SubdomainAlreadyTaken;
use App\Application\Iam\RegisterOwner\RegisterOwnerCommand;
use App\Application\Iam\RegisterOwner\RegisterOwnerHandler;
use App\Domain\Iam\SubdomainSlug;
use App\Interface\TelegramBot\Support\TelegramMessages;
use App\Interface\TelegramBot\Support\TelegramOwnerResolver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

final class OnboardingConversation extends Conversation
{
    private ?string $email = null;

    protected function getSerializableAttributes(): array
    {
        return [
            'email' => $this->email,
        ];
    }

    public function start(Nutgram $bot): void
    {
        $owner = app(TelegramOwnerResolver::class)->resolve($bot);

        if ($owner !== null) {
            $bot->sendMessage(TelegramMessages::mainMenu());
            $this->end();

            return;
        }

        $bot->sendMessage(
            "Добро пожаловать в Guard Reviews!\n\n".
            'Введите email для уведомлений (обязательно):'
        );
        $this->next('askEmail');
    }

    public function askEmail(Nutgram $bot): void
    {
        $email = trim((string) $bot->message()?->text);

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'max:255']],
            [
                'email.required' => 'Укажите email.',
                'email.email' => 'Некорректный email.',
            ],
        );

        if ($validator->fails()) {
            $bot->sendMessage($validator->errors()->first('email')."\n\nВведите email ещё раз:");
            $this->next('askEmail');

            return;
        }

        $this->email = $email;

        $domain = config('guardreviews.domain');
        $bot->sendMessage(
            "Придумайте адрес вашего сайта.\n".
            "Например: kafe-vesna → https://kafe-vesna.{$domain}"
        );
        $this->next('askSlug');
    }

    public function askSlug(Nutgram $bot): void
    {
        $slug = $this->normalizeSlug((string) $bot->message()?->text);

        try {
            $domainSlug = new SubdomainSlug($slug);
        } catch (InvalidArgumentException $e) {
            $bot->sendMessage($e->getMessage()."\n\nВведите другой адрес:");
            $this->next('askSlug');

            return;
        }

        try {
            app(RegisterOwnerHandler::class)->handle(new RegisterOwnerCommand(
                name: $bot->user()?->first_name ?? 'Владелец',
                email: (string) $this->email,
                subdomain: $domainSlug->value,
                telegramId: (string) $bot->userId(),
            ));
        } catch (SubdomainAlreadyTaken) {
            $bot->sendMessage("Адрес «{$domainSlug->value}» уже занят.\n\nВведите другой:");
            $this->next('askSlug');

            return;
        }

        $domain = config('guardreviews.domain');

        $bot->sendMessage(
            "Аккаунт создан!\n".
            "Ваш адрес: https://{$domainSlug->value}.{$domain}\n\n".
            "Подписка не активна — оплатите через /pay, чтобы QR-коды работали.\n\n".
            TelegramMessages::mainMenu()
        );

        $this->end();
    }

    private function normalizeSlug(string $input): string
    {
        return Str::of($input)
            ->lower()
            ->replaceMatches('/[^a-z0-9-]+/', '-')
            ->trim('-')
            ->toString();
    }
}
