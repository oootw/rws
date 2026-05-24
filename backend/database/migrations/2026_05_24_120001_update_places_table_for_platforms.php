<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->json('platforms')->nullable()->after('title');
        });

        foreach (
            DB::table('places')
                ->whereNotNull('link_2gis')
                ->where('link_2gis', '!=', '')
                ->orderBy('id')
                ->cursor() as $place
        ) {
            DB::table('places')
                ->where('id', $place->id)
                ->update([
                    'platforms' => json_encode([
                        [
                            'type' => '2gis',
                            'url' => $place->link_2gis,
                            'label' => '2GIS',
                        ],
                    ], JSON_THROW_ON_ERROR),
                ]);
        }

        Schema::table('places', function (Blueprint $table): void {
            $table->dropUnique(['hash']);
            $table->dropColumn(['hash', 'link_2gis']);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->string('hash')->nullable()->unique()->after('user_id');
            $table->string('link_2gis')->nullable()->after('title');
        });

        foreach (
            DB::table('places')
                ->whereNotNull('platforms')
                ->orderBy('id')
                ->cursor() as $place
        ) {
            $platforms = json_decode($place->platforms, true, 512, JSON_THROW_ON_ERROR);
            $link2gis = collect($platforms)->firstWhere('type', '2gis')['url'] ?? null;

            DB::table('places')
                ->where('id', $place->id)
                ->update(['link_2gis' => $link2gis]);
        }

        Schema::table('places', function (Blueprint $table): void {
            $table->dropColumn('platforms');
        });
    }
};
