<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table): void {
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        // Backfill: до этой миграции «default» определялся по title='MVP'
        // (см. EloquentTariffRepository::DEFAULT_TITLE). Сохраняем то же поведение.
        DB::table('tariffs')->where('title', 'MVP')->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table): void {
            $table->dropColumn('is_default');
        });
    }
};
