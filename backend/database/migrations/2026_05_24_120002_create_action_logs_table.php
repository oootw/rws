<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('action_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('place_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['place_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
