<?php

declare(strict_types=1);

use App\Domain\Analytics\WeeklySummary;

it('считает 0% конверсию когда нет сканов', function (): void {
    $summary = new WeeklySummary(scanned: 0, redirectedExternal: 5, leftNegative: 0);

    expect($summary->externalConversionPercent())->toBe(0.0);
});

it('считает процентную конверсию от сканов', function (): void {
    $summary = new WeeklySummary(scanned: 10, redirectedExternal: 4, leftNegative: 1);

    expect($summary->externalConversionPercent())->toBe(40.0);
});

it('строит пустую сводку', function (): void {
    $summary = WeeklySummary::empty();

    expect($summary->scanned)->toBe(0)
        ->and($summary->redirectedExternal)->toBe(0)
        ->and($summary->leftNegative)->toBe(0);
});
