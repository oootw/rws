<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_push_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('p256dh');
            $table->string('auth');
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('last_seen_at')->nullable();

            $table->unique('endpoint');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_push_subscriptions');
    }
};
