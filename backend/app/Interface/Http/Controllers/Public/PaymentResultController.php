<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Public;

use Illuminate\Http\Response;

final class PaymentResultController
{
    public function success(): Response
    {
        return response(
            "Оплата прошла успешно.\n\n".
            'Подписка будет активирована в течение минуты. Вернитесь в Telegram-бот и проверьте /subscription.',
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    public function fail(): Response
    {
        return response(
            "Оплата не завершена.\n\n".
            'Попробуйте снова через команду /pay в Telegram-боте.',
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
