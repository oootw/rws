<?php

declare(strict_types=1);

use App\Domain\Reviews\Stars;

it('считает 1-3 звезды негативом', function (int $value): void {
    expect((new Stars($value))->isNegative())->toBeTrue();
})->with([1, 2, 3]);

it('считает 4-5 звёзд не негативом', function (int $value): void {
    expect((new Stars($value))->isNegative())->toBeFalse();
})->with([4, 5]);

it('отвергает выход за диапазон', function (int $value): void {
    new Stars($value);
})->with([0, 6, -1, 100])->throws(InvalidArgumentException::class);
