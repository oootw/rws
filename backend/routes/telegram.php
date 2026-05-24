<?php

/** @var Nutgram $bot */

use App\Interface\TelegramBot\Callbacks\ReviewCallbackHandler;
use App\Interface\TelegramBot\Commands\BotCommandHandler;
use App\Interface\TelegramBot\Conversations\AddPlaceConversation;
use App\Interface\TelegramBot\Conversations\OnboardingConversation;
use App\Interface\TelegramBot\Middleware\RequireRegisteredOwner;
use SergiX44\Nutgram\Nutgram;

$bot->onCommand('start', OnboardingConversation::class)
    ->description('Регистрация и главное меню');

$bot->group(function (Nutgram $bot): void {
    $bot->onCommand('addplace', AddPlaceConversation::class)
        ->description('Добавить точку');

    $bot->onCommand('places', [BotCommandHandler::class, 'places'])
        ->description('Список точек');

    $bot->onCommand('reviews', [BotCommandHandler::class, 'reviews'])
        ->description('Негативные отзывы');

    $bot->onCommand('subscription', [BotCommandHandler::class, 'subscription'])
        ->description('Статус подписки');

    $bot->onCommand('pay', [BotCommandHandler::class, 'pay'])
        ->description('Оплатить подписку');

    $bot->onCommand('link', [BotCommandHandler::class, 'link'])
        ->description('Привязать MAX');
})->middleware(RequireRegisteredOwner::class);

$bot->onCallbackQueryData('place:info:{placeId}', [BotCommandHandler::class, 'placeInfo']);
$bot->onCallbackQueryData('place:qr:{placeId}', [BotCommandHandler::class, 'sendQr']);
$bot->onCallbackQueryData('place:analytics:{placeId}', [BotCommandHandler::class, 'analytics']);
$bot->onCallbackQueryData('review:{reviewId}:{status}', ReviewCallbackHandler::class);
