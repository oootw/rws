<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Domain\Iam\Owner;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Отдаёт собранный SPA-shell Owner-панели (frontend/owner/dist/index.html).
 *
 * Монтируется как catch-all на `/owner/{any?}` под middleware `tenant`:
 * SPA сама рулит навигацию, бэк только возвращает один index.html.
 * Тенант обязателен — без живого поддомена страница не должна открываться.
 */
final class OwnerSpaController
{
    /** Путь к собранному фронту относительно base_path. */
    private const DIST_PATH = '../dist/owner/index.html';

    public function __invoke(Request $request, Filesystem $filesystem): Response
    {
        $tenant = $request->attributes->get('tenant');

        if (! $tenant instanceof Owner) {
            throw new NotFoundHttpException;
        }

        try {
            $html = $filesystem->get(base_path(self::DIST_PATH));
        } catch (FileNotFoundException) {
            throw new NotFoundHttpException(
                'Owner SPA bundle not built. Run `npm run build:owner` in frontend/.',
            );
        }

        return new Response(
            content: $html,
            status: 200,
            headers: [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ],
        );
    }
}
