<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * До B-фаз `tariffs.features` хранил либо null, либо ассоц-массив
 * (например `{extra_place_price: 29000}`). Mapper это уже глотает,
 * но в БД хочется чистый формат — list<string>.
 *
 * Up: всё, что не json-массив строк → []. Down: no-op (форма уже нормализована).
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('tariffs')->select('id', 'features')->get();

        foreach ($rows as $row) {
            $raw = $row->features;
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

            if (! is_array($decoded)) {
                DB::table('tariffs')->where('id', $row->id)->update(['features' => json_encode([])]);

                continue;
            }

            $normalized = array_values(array_filter(
                $decoded,
                static fn ($v): bool => is_string($v),
            ));

            // Если был list<string> и форма не поменялась — пропускаем.
            if ($normalized === $decoded && array_is_list($decoded)) {
                continue;
            }

            DB::table('tariffs')->where('id', $row->id)->update(['features' => json_encode($normalized)]);
        }
    }

    public function down(): void {}
};
