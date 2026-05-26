<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table): void {
            $table->integer('extra_place_price')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table): void {
            $table->dropColumn('extra_place_price');
        });
    }
};
