<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use InvalidArgumentException;

/**
 * VO Telegram chat_id для групповых/супергрупповых чатов.
 *
 * Telegram присваивает группам отрицательные числовые идентификаторы
 * (обычные группы — короткие отрицательные, супергруппы — длинные
 * вида -100xxxxxxxxxx). DM с пользователем не относится к этому VO —
 * для DM используется {@see TelegramId}.
 */
final readonly class TelegramChatId
{
    private const PATTERN = '/^-\d+$/';

    public function __construct(public string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(
                'TelegramChatId должен быть отрицательным числовым идентификатором группового чата.'
            );
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
