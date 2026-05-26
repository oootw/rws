<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;
use Throwable;

/**
 * Кастомная страница состояния Telegram-бота.
 *
 * Дёргает Nutgram::getMe() и getWebhookInfo() (cached 30s через Cache::remember),
 * чтобы не бомбардировать Telegram при перерендере страницы Livewire.
 *
 * Actions:
 *  - set_webhook (URL берётся из APP_URL/api/webhooks/telegram по умолчанию);
 *  - delete_webhook (опасно — после этого бот перестанет принимать апдейты, требует подтверждение);
 *  - send_test_message — только не-production, защита через `! app()->isProduction()`.
 */
final class TelegramBotPage extends Page
{
    private const CACHE_KEY = 'admin:telegram-bot:info';

    private const CACHE_TTL_SECONDS = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Telegram бот';

    protected static ?string $title = 'Telegram бот';

    protected static string|\UnitEnum|null $navigationGroup = 'Операционная';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.telegram-bot';

    /** @var array<string, mixed> */
    public array $me = [];

    /** @var array<string, mixed> */
    public array $webhook = [];

    public ?string $loadError = null;

    public function mount(): void
    {
        $this->refresh(force: false);
    }

    public function refresh(bool $force = true): void
    {
        if ($force) {
            Cache::forget(self::CACHE_KEY);
        }

        try {
            $cached = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
                $bot = app(Nutgram::class);

                return [
                    'me' => self::structToArray($bot->getMe()),
                    'webhook' => self::structToArray($bot->getWebhookInfo()),
                ];
            });

            $this->me = $cached['me'] ?? [];
            $this->webhook = $cached['webhook'] ?? [];
            $this->loadError = null;
        } catch (Throwable $e) {
            $this->me = [];
            $this->webhook = [];
            $this->loadError = $e->getMessage();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->refresh()),

            Action::make('set_webhook')
                ->label('Установить webhook')
                ->icon('heroicon-o-link')
                ->schema([
                    TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->required()
                        ->default(rtrim((string) config('app.url'), '/').'/api/webhooks/telegram'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(Nutgram::class)->setWebhook((string) $data['url']);

                        Notification::make()->title('Webhook установлен')->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title('Не удалось установить webhook')->body($e->getMessage())->danger()->send();
                    }

                    $this->refresh();
                }),

            Action::make('delete_webhook')
                ->label('Удалить webhook')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Удалить webhook?')
                ->modalDescription('После этого бот перестанет получать апдейты, пока не выставите webhook снова.')
                ->action(function (): void {
                    try {
                        app(Nutgram::class)->deleteWebhook();

                        Notification::make()->title('Webhook удалён')->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title('Не удалось удалить webhook')->body($e->getMessage())->danger()->send();
                    }

                    $this->refresh();
                }),

            Action::make('send_test_message')
                ->label('Тестовое сообщение')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (): bool => ! app()->isProduction())
                ->schema([
                    TextInput::make('chat_id')
                        ->label('Chat ID')
                        ->required()
                        ->helperText('Числовой ID получателя — узнаётся через @userinfobot.'),
                    TextInput::make('text')
                        ->label('Текст')
                        ->required()
                        ->default('Test message from Guard Reviews admin'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(Nutgram::class)->sendMessage(
                            text: (string) $data['text'],
                            chat_id: (string) $data['chat_id'],
                        );

                        Notification::make()->title('Сообщение отправлено')->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title('Ошибка отправки')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    /**
     * Преобразует объект Nutgram (User/WebhookInfo) в array для blade.
     * У всех Nutgram-структур есть toArray(), но на случай отсутствия —
     * fallback через get_object_vars.
     *
     * @return array<string, mixed>
     */
    private static function structToArray(mixed $struct): array
    {
        if ($struct === null) {
            return [];
        }

        if (is_array($struct)) {
            return $struct;
        }

        if (is_object($struct) && method_exists($struct, 'toArray')) {
            $array = $struct->toArray();

            return is_array($array) ? $array : [];
        }

        if (is_object($struct)) {
            return get_object_vars($struct);
        }

        return [];
    }
}
