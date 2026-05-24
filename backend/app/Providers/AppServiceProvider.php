<?php

namespace App\Providers;

use App\Contracts\Captcha\CaptchaVerifierInterface;
use App\Services\Captcha\NullCaptchaVerifier;
use App\Services\Captcha\YandexSmartCaptchaVerifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CaptchaVerifierInterface::class, function (): CaptchaVerifierInterface {
            $serverKey = config('guardreviews.captcha.server_key');

            if (is_string($serverKey) && $serverKey !== '') {
                return new YandexSmartCaptchaVerifier;
            }

            return new NullCaptchaVerifier;
        });
    }

    public function boot(): void
    {
        //
    }
}
