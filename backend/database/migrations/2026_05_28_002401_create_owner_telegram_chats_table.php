<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_telegram_chats', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('chat_id');
            $table->string('title')->nullable();
            $table->timestamp('linked_at');

            $table->unique(['owner_id', 'chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_telegram_chats');
    }
};
