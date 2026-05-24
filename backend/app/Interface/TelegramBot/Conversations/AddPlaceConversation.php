<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Conversations;

use App\Application\Places\RegisterPlace\RegisterPlaceCommand;
use App\Application\Places\RegisterPlace\RegisterPlaceHandler;
use App\Interface\TelegramBot\Support\TelegramOwnerResolver;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

final class AddPlaceConversation extends Conversation
{
    private string $title = '';

    /** @var list<array{type: string, url: string, label: string}> */
    private array $platforms = [];

    protected function getSerializableAttributes(): array
    {
        return [
            'title' => $this->title,
            'platforms' => $this->platforms,
        ];
    }

    public function start(Nutgram $bot): void
    {
        $owner = app(TelegramOwnerResolver::class)->resolve($bot);

        if ($owner === null) {
            $bot->sendMessage('Сначала пройдите регистрацию: /start');
            $this->end();

            return;
        }

        $bot->sendMessage('Введите название точки (например: Кафе Уют):');
        $this->next('askTitle');
    }

    public function askTitle(Nutgram $bot): void
    {
        $title = trim((string) $bot->message()?->text);

        if ($title === '') {
            $bot->sendMessage('Название не может быть пустым. Введите название точки:');
            $this->next('askTitle');

            return;
        }

        $this->title = $title;

        $bot->sendMessage(
            "Отправьте ссылку на 2GIS.\n".
            'Или отправьте «-», чтобы пропустить.'
        );
        $this->next('ask2gis');
    }

    public function ask2gis(Nutgram $bot): void
    {
        $url = trim((string) $bot->message()?->text);

        if ($url !== '-' && $url !== '') {
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                $bot->sendMessage('Некорректная ссылка. Отправьте URL 2GIS или «-»:');
                $this->next('ask2gis');

                return;
            }

            $this->platforms[] = [
                'type' => '2gis',
                'url' => $url,
                'label' => '2GIS',
            ];
        }

        $bot->sendMessage(
            "Отправьте ссылку на Яндекс Карты.\n".
            'Или отправьте «-», чтобы пропустить.'
        );
        $this->next('askYandex');
    }

    public function askYandex(Nutgram $bot): void
    {
        $url = trim((string) $bot->message()?->text);

        if ($url !== '-' && $url !== '') {
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                $bot->sendMessage('Некорректная ссылка. Отправьте URL Яндекс Карт или «-»:');
                $this->next('askYandex');

                return;
            }

            $this->platforms[] = [
                'type' => 'yandex',
                'url' => $url,
                'label' => 'Яндекс Карты',
            ];
        }

        $bot->sendMessage(
            "Отправьте URL фонового изображения для scan-формы.\n".
            'Или отправьте «-», чтобы пропустить.'
        );
        $this->next('askBackground');
    }

    public function askBackground(Nutgram $bot): void
    {
        $owner = app(TelegramOwnerResolver::class)->resolve($bot);

        if ($owner === null) {
            $this->end();

            return;
        }

        $background = trim((string) $bot->message()?->text);
        $backgroundUrl = ($background === '' || $background === '-') ? null : $background;

        if ($backgroundUrl !== null && ! filter_var($backgroundUrl, FILTER_VALIDATE_URL)) {
            $bot->sendMessage('Некорректная ссылка на изображение. Отправьте URL или «-»:');
            $this->next('askBackground');

            return;
        }

        app(RegisterPlaceHandler::class)->handle(new RegisterPlaceCommand(
            ownerId: $owner->id->value,
            title: $this->title,
            platforms: $this->platforms,
            backgroundImageUrl: $backgroundUrl,
        ));

        $bot->sendMessage(
            "Точка «{$this->title}» создана.\n\n".
            'Посмотреть список: /places'
        );

        $this->end();
    }
}
