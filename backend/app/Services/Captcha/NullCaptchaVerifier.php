<?php

namespace App\Services\Captcha;

use App\Contracts\Captcha\CaptchaVerifierInterface;

/**
 * Used in local/testing when captcha keys are not configured.
 */
final class NullCaptchaVerifier implements CaptchaVerifierInterface
{
    public function verify(string $token, ?string $ip = null): bool
    {
        return $token !== '';
    }
}
