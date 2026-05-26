<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('admin_email');
            $table->string('action');
            $table->string('resource')->nullable();
            $table->string('record_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->timestamp('created_at');

            $table->index('action');
            $table->index('created_at');
            $table->index(['resource', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
