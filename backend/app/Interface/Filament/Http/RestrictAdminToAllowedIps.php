<?php

declare(strict_types=1);

namespace App\Interface\Filament\Http;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Allow-list IP-адресов для /admin.
 *
 * Список читается из config('guardreviews.admin.allowed_ips') (CSV в env
 * ADMIN_ALLOWED_IPS). Пустой список = middleware отключён (полезно для local/CI).
 * Поддерживает индивидуальные IP и CIDR-маски — делегируем Symfony IpUtils.
 */
final readonly class RestrictAdminToAllowedIps
{
    public function __construct(private Config $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        $allowed = $this->parseAllowList((string) $this->config->get('guardreviews.admin.allowed_ips', ''));

        if ($allowed === []) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        if (! IpUtils::checkIp($ip, $allowed)) {
            throw new AccessDeniedHttpException('Доступ к админ-панели с этого IP запрещён.');
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function parseAllowList(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', $csv),
        ), static fn (string $value): bool => $value !== ''));
    }
}
