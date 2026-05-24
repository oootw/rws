<?php

declare(strict_types=1);

namespace App\Domain\Analytics;

/**
 * Сводка действий по точке за неделю.
 * Хранится как иммутабельный read-model: считается отдельным запросом,
 * не восстанавливается из ActionLog в памяти.
 */
final readonly class WeeklySummary
{
    public function __construct(
        public int $scanned,
        public int $redirectedExternal,
        public int $leftNegative,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * Конверсия скан → переход на внешнюю площадку (%).
     */
    public function externalConversionPercent(): float
    {
        if ($this->scanned === 0) {
            return 0.0;
        }

        return round(($this->redirectedExternal / $this->scanned) * 100, 1);
    }
}
