<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->unsignedTinyInteger('hour_of_day');
            $table->float('predicted_wait_minutes')->default(0);
            $table->unsignedInteger('predicted_volume')->default(0);
            $table->boolean('is_peak')->default(false);
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'queue_id', 'day_of_week', 'hour_of_day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
