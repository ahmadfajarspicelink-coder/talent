<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('cpu_usage')->nullable();       // persen 0-100
            $table->unsignedBigInteger('memory_used')->nullable();      // bytes
            $table->unsignedBigInteger('memory_total')->nullable();     // bytes
            $table->unsignedInteger('uptime_ticks')->nullable();        // timeticks (1/100 sec)
            $table->timestamp('polled_at');

            $table->index(['device_id', 'polled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_stats');
    }
};
