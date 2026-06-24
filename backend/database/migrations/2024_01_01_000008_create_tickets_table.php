<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('guichet_session_id')->nullable()->constrained('guichet_sessions')->nullOnDelete();
            $table->string('ticket_number', 20);
            $table->enum('status', ['waiting', 'called', 'served', 'skipped', 'cancelled'])->default('waiting');
            $table->enum('priority', ['normal', 'priority'])->default('normal');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('idle_time_seconds')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'queue_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
