<?php

namespace App\Services\Captcha;

use App\Contracts\Captcha\CaptchaVerifierInterface;
use Illuminate\Support\Facades\Http;

final class YandexSmartCaptchaVerifier implements CaptchaVerifierInterface
{
    private const VALIDATE_URL = 'https://smartcaptcha.yandexcloud.net/validate';

    public function verify(string $token, ?string $ip = null): bool
    {
        $secret = config('guardreviews.captcha.server_key');

        if (! is_string($secret) || $secret === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(5)
            ->post(self::VALIDATE_URL, array_filter([
                'secret' => $secret,
                'token' => $token,
                'ip' => $ip,
            ]));

        if (! $response->ok()) {
            return false;
        }

        return $response->json('status') === 'ok';
    }
}
