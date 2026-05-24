<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Guzzle-middleware: пул URL'ов к api.telegram.org с автоматическим failover'ом.
 *
 * При сетевой ошибке (ConnectException, timeout, отсутствие ответа) или HTTP
 * 502/503/504 от текущего URL — переписывает scheme/host/port запроса на
 * следующий URL пула и повторяет. Если все упали — пробрасывается первое
 * исключение, и job уходит на следующий retry (с backoff'ом).
 *
 * НЕ повторяет на 4xx (включая 429): это смысловой ответ Telegram'а, его
 * повторять через другой прокси бессмысленно.
 */
final readonly class TelegramFailoverMiddleware
{
    /**
     * @param  list<string>  $urls  Список base URL'ов к Telegram Bot API.
     *                              Первый — primary, остальные — фолбэки.
     */
    public function __construct(
        private array $urls,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            return $this->attempt($handler, $request, $options, 0, []);
        };
    }

    /**
     * @param  list<Throwable>  $errors
     */
    private function attempt(
        callable $handler,
        RequestInterface $request,
        array $options,
        int $index,
        array $errors,
    ): PromiseInterface {
        if ($index >= count($this->urls)) {
            $first = $errors[0] ?? new RuntimeException('Не задано ни одного Telegram API URL.');

            throw $first;
        }

        $rewritten = $this->rewrite($request, $this->urls[$index]);

        return $handler($rewritten, $options)->then(
            function (ResponseInterface $response) use ($handler, $request, $options, $index, $errors) {
                $code = $response->getStatusCode();

                if (in_array($code, [502, 503, 504], true)) {
                    $this->logger->warning('Telegram URL вернул ошибку, пробуем следующий', [
                        'url' => $this->urls[$index],
                        'status' => $code,
                    ]);

                    $errors[] = new RuntimeException("HTTP {$code} from {$this->urls[$index]}");

                    return $this->attempt($handler, $request, $options, $index + 1, $errors);
                }

                if ($index > 0) {
                    $this->logger->info('Telegram URL ответил после фолбэка', [
                        'url' => $this->urls[$index],
                        'failover_index' => $index,
                    ]);
                }

                return $response;
            },
            function (Throwable $reason) use ($handler, $request, $options, $index, $errors) {
                if ($this->isRetryable($reason)) {
                    $this->logger->warning('Telegram URL не отвечает, пробуем следующий', [
                        'url' => $this->urls[$index],
                        'error' => $reason->getMessage(),
                    ]);

                    $errors[] = $reason;

                    return $this->attempt($handler, $request, $options, $index + 1, $errors);
                }

                throw $reason;
            },
        );
    }

    private function rewrite(RequestInterface $request, string $baseUrl): RequestInterface
    {
        $parts = parse_url($baseUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException("Некорректный Telegram API URL: {$baseUrl}");
        }

        $uri = $request->getUri()
            ->withScheme($parts['scheme'])
            ->withHost($parts['host']);

        $uri = isset($parts['port'])
            ? $uri->withPort($parts['port'])
            : $uri->withPort(null);

        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            // Если в URL пула есть префикс пути (https://tg.example.com/v1)
            // — пристыковываем его перед путём запроса (/bot.../sendMessage).
            $uri = $uri->withPath(rtrim($parts['path'], '/').$request->getUri()->getPath());
        }

        return $request->withUri($uri)->withHeader('Host', $parts['host']);
    }

    private function isRetryable(Throwable $reason): bool
    {
        if ($reason instanceof ConnectException) {
            return true;
        }

        if ($reason instanceof RequestException && $reason->getResponse() === null) {
            return true;
        }

        return false;
    }
}
