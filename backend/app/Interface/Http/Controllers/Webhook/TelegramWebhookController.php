<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Webhook;

use Illuminate\Http\Response;
use SergiX44\Nutgram\Nutgram;

final class TelegramWebhookController
{
    public function __invoke(Nutgram $bot): Response
    {
        $bot->run();

        return response('OK');
    }
}
