<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Журнал попыток доставки уведомлений владельцу. Пишется из
     * MultiChannelOwnerNotifier по каждой попытке (success/failure
     * по каждому каналу). Используется только для админ-просмотра —
     * никакая бизнес-логика на него не опирается.
     *
     * owner_id namespace nullable — для уведомлений без идентифицируемого
     * владельца (например, e-mail на ADMIN_ALERT_EMAIL). FK не ставим,
     * чтобы не каскадить и не блокировать DeleteOwner: запись остаётся как лог.
     */
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('owner_id')->nullable()->index();
            $table->string('channel', 64);
            $table->string('kind', 64);
            $table->string('status', 16);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
