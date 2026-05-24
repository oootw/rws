<?php

namespace App\Services;

use App\Domain\Places\Place;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * QR и связанные с QR ссылки.
 *
 * Сервис принимает доменный Place и заранее посчитанный scan-base URL —
 * политика "какой поддомен у владельца" к QR никак не относится.
 */
final class QrCodeService
{
    public function placeScanUrl(string $scanBaseUrl, Place $place): string
    {
        $base = rtrim($scanBaseUrl, '/');

        return "{$base}/s/{$place->id->value}";
    }

    public function pngBytes(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            size: 400,
            margin: 10,
        );

        return (new PngWriter)->write($qrCode)->getString();
    }

    public function designOrderUrl(Place $place): ?string
    {
        $username = config('guardreviews.founder.telegram_username');

        if (! is_string($username) || $username === '') {
            return null;
        }

        $username = ltrim($username, '@');

        return "https://t.me/{$username}?start=design_{$place->id->value}";
    }
}
