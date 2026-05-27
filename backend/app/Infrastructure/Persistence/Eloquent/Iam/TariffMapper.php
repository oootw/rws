<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Iam;

use App\Domain\Iam\Feature;
use App\Domain\Iam\Tariff;
use App\Domain\Iam\TariffId;
use App\Models\Tariff as TariffModel;

final class TariffMapper
{
    public function toDomain(TariffModel $model): Tariff
    {
        return new Tariff(
            id: new TariffId((string) $model->id),
            title: (string) $model->title,
            basePrice: (int) $model->price,
            extraPlacePrice: (int) $model->extra_place_price,
            isDefault: (bool) $model->is_default,
            placesLimit: $model->places_limit === null ? null : (int) $model->places_limit,
            features: self::mapFeatures($model->features),
        );
    }

    /**
     * Прощает legacy-формат assoc-массива ({extra_place_price: 29000}) и null:
     * не-строковые значения и неизвестные ключи отбрасываются молча — старые
     * данные в БД не должны валить чтение.
     *
     * @return list<Feature>
     */
    private static function mapFeatures(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $features = [];
        foreach ($raw as $value) {
            if (! is_string($value)) {
                continue;
            }
            $feature = Feature::tryFrom($value);
            if ($feature !== null) {
                $features[] = $feature;
            }
        }

        return $features;
    }
}
