<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Internal;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Caddy on-demand TLS ask-эндпоинт.
 *
 * Caddy дергает этот URL с ?domain=<host> перед выпуском сертификата.
 * Возвращаем 200 — если host равен одному из разрешённых apex-доменов или
 * выглядит как корректный поддомен такого apex-а. Защищает от выпуска
 * левых сертификатов и попадания на rate-limit Let's Encrypt.
 *
 * Список apex-доменов — TLS_ALLOWED_DOMAINS (csv) или одно значение APP_DOMAIN.
 */
final class TlsAllowController
{
    public function __invoke(Request $request): Response
    {
        $host = strtolower(trim((string) $request->query('domain')));

        if ($host === '') {
            return response('not allowed', 404);
        }

        foreach ($this->allowedDomains() as $domain) {
            if ($host === $domain) {
                return response('ok', 200);
            }

            if (str_ends_with($host, '.'.$domain)) {
                $slug = substr($host, 0, -strlen('.'.$domain));

                if (preg_match('/^[a-z0-9][a-z0-9-]{1,62}[a-z0-9]$/', $slug) === 1) {
                    return response('ok', 200);
                }
            }
        }

        return response('not allowed', 404);
    }

    /**
     * @return list<string>
     */
    private function allowedDomains(): array
    {
        $csv = (string) config('guardreviews.tls_allowed_domains', '');

        $domains = $csv !== ''
            ? array_filter(array_map('trim', explode(',', $csv)))
            : [];

        if ($domains === []) {
            $fallback = (string) config('guardreviews.domain');
            $domains = $fallback !== '' ? [$fallback] : [];
        }

        return array_values(array_map('strtolower', $domains));
    }
}
