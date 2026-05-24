<?php

declare(strict_types=1);

namespace App\Interface\TelegramBot\Commands;

use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryHandler;
use App\Application\Analytics\GetWeeklySummary\GetWeeklySummaryQuery;
use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountHandler;
use App\Application\Iam\CalculateSubscriptionAmount\CalculateSubscriptionAmountQuery;
use App\Application\Notifications\SendWeeklyDigest\SendWeeklyDigestCommand;
use App\Application\Notifications\SendWeeklyDigest\SendWeeklyDigestHandler;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentCommand;
use App\Application\Payments\InitSubscriptionPayment\InitSubscriptionPaymentHandler;
use App\Application\Places\GetPlaceForOwner\GetPlaceForOwnerHandler;
use App\Application\Places\GetPlaceForOwner\GetPlaceForOwnerQuery;
use App\Application\Places\ListOwnerPlaces\ListOwnerPlacesHandler;
use App\Application\Places\ListOwnerPlaces\ListOwnerPlacesQuery;
use App\Application\Reviews\ListRecentReviewsForOwner\ListRecentReviewsForOwnerHandler;
use App\Application\Reviews\ListRecentReviewsForOwner\ListRecentReviewsForOwnerQuery;
use App\Application\Reviews\ListRecentReviewsForOwner\RecentReviewView;
use App\Domain\Notifications\OwnerContact;
use App\Interface\TelegramBot\Support\TelegramMessages;
use App\Interface\TelegramBot\Support\TelegramOwnerResolver;
use App\Services\QrCodeService;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Input\InputFile;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

final class BotCommandHandler
{
    public function __construct(
        private readonly TelegramOwnerResolver $ownerResolver,
        private readonly ListOwnerPlacesHandler $listOwnerPlaces,
        private readonly GetPlaceForOwnerHandler $getPlaceForOwner,
        private readonly ListRecentReviewsForOwnerHandler $listRecentReviews,
        private readonly GetWeeklySummaryHandler $getWeeklySummary,
        private readonly SendWeeklyDigestHandler $sendWeeklyDigest,
        private readonly CalculateSubscriptionAmountHandler $calculateAmount,
        private readonly InitSubscriptionPaymentHandler $initSubscriptionPayment,
        private readonly QrCodeService $qrCodeService,
    ) {}

    public function places(Nutgram $bot): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $places = $this->listOwnerPlaces->handle(
            new ListOwnerPlacesQuery(ownerId: $owner->id->value),
        );

        if ($places === []) {
            $bot->sendMessage('У вас пока нет точек. Создайте первую: /addplace');

            return;
        }

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($places as $place) {
            $keyboard->addRow(
                InlineKeyboardButton::make("📍 {$place->title}", callback_data: "place:info:{$place->id}"),
            );
        }

        $bot->sendMessage('Ваши точки:', reply_markup: $keyboard);
    }

    public function reviews(Nutgram $bot): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $reviews = $this->listRecentReviews->handle(
            new ListRecentReviewsForOwnerQuery(ownerId: $owner->id->value),
        );

        if ($reviews === []) {
            $bot->sendMessage('Негативных отзывов пока нет.');

            return;
        }

        $lines = array_map(
            static fn (RecentReviewView $review): string => implode("\n", [
                "⭐{$review->stars} — {$review->placeTitle}",
                "Статус: {$review->status->value}",
                "Контакт: {$review->contact}",
                mb_strimwidth($review->text, 0, 120, '…'),
                '---',
            ]),
            $reviews,
        );

        $bot->sendMessage(implode("\n", $lines));
    }

    public function subscription(Nutgram $bot): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $bot->sendMessage(TelegramMessages::subscriptionStatus($owner->subscription()->endsAt));
    }

    public function pay(Nutgram $bot): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $amount = $this->calculateAmount->handle(
            new CalculateSubscriptionAmountQuery(ownerId: $owner->id->value),
        );
        $rubles = number_format($amount / 100, 0, ',', ' ');
        $placesCount = count($this->listOwnerPlaces->handle(
            new ListOwnerPlacesQuery(ownerId: $owner->id->value),
        ));

        $result = $this->initSubscriptionPayment->handle(
            new InitSubscriptionPaymentCommand(ownerId: $owner->id->value),
        );

        if ($result->paymentUrl === null) {
            $bot->sendMessage(
                "Сумма к оплате: {$rubles} ₽/мес\n".
                "Точек: {$placesCount}\n\n".
                ($result->errorMessage ?? 'Оплата временно недоступна.')
            );

            return;
        }

        $keyboard = InlineKeyboardMarkup::make()->addRow(
            InlineKeyboardButton::make("Оплатить {$rubles} ₽", url: $result->paymentUrl),
        );

        $bot->sendMessage(
            "Сумма к оплате: {$rubles} ₽/мес\n".
            "Точек: {$placesCount}\n\n".
            'Нажмите кнопку ниже, чтобы перейти к оплате через Tinkoff.',
            reply_markup: $keyboard,
        );
    }

    public function link(Nutgram $bot): void
    {
        $bot->sendMessage(
            'Привязка MAX будет доступна позже. '.
            'Пока используйте Telegram для управления.'
        );
    }

    public function analytics(Nutgram $bot, string $placeId): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $place = $this->getPlaceForOwner->handle(new GetPlaceForOwnerQuery(
            placeId: $placeId,
            ownerId: $owner->id->value,
        ));

        if ($place === null) {
            $bot->sendMessage('Точка не найдена.');

            return;
        }

        $summary = $this->getWeeklySummary->handle(
            new GetWeeklySummaryQuery(placeId: $place->id->value),
        );

        $this->sendWeeklyDigest->handle(new SendWeeklyDigestCommand(
            contact: new OwnerContact(
                telegramId: $owner->telegramId()?->value,
                maxId: null,
                email: null,
            ),
            placeTitle: $place->title()->value,
            summary: $summary,
        ));
    }

    public function sendQr(Nutgram $bot, string $placeId): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $place = $this->getPlaceForOwner->handle(new GetPlaceForOwnerQuery(
            placeId: $placeId,
            ownerId: $owner->id->value,
        ));

        if ($place === null) {
            $bot->sendMessage('Точка не найдена.');

            return;
        }

        $url = $this->qrCodeService->placeScanUrl(
            $owner->scanBaseUrl((string) config('guardreviews.domain')),
            $place,
        );
        $png = $this->qrCodeService->pngBytes($url);

        $designUrl = $this->qrCodeService->designOrderUrl($place);

        $keyboard = $designUrl === null
            ? null
            : InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('Заказать дизайн QR', url: $designUrl),
            );

        $bot->sendPhoto(
            photo: InputFile::make($png, 'qr.png'),
            caption: "QR для «{$place->title()->value}»\n{$url}",
            reply_markup: $keyboard,
        );
    }

    public function placeInfo(Nutgram $bot, string $placeId): void
    {
        $owner = $this->ownerResolver->resolve($bot);

        if ($owner === null) {
            return;
        }

        $place = $this->getPlaceForOwner->handle(new GetPlaceForOwnerQuery(
            placeId: $placeId,
            ownerId: $owner->id->value,
        ));

        if ($place === null) {
            $bot->sendMessage('Точка не найдена.');

            return;
        }

        $scanUrl = $this->qrCodeService->placeScanUrl(
            $owner->scanBaseUrl((string) config('guardreviews.domain')),
            $place,
        );

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('QR-код', callback_data: "place:qr:{$place->id->value}"),
                InlineKeyboardButton::make('Аналитика', callback_data: "place:analytics:{$place->id->value}"),
            );

        $bot->sendMessage(
            "<b>{$place->title()->value}</b>\n".
            'Площадок: '.count($place->platforms())."\n".
            "Ссылка: {$scanUrl}",
            parse_mode: ParseMode::HTML,
            reply_markup: $keyboard,
        );
    }
}
