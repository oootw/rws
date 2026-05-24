<?php

namespace App\Contracts\Captcha;

interface CaptchaVerifierInterface
{
    public function verify(string $token, ?string $ip = null): bool;
}
