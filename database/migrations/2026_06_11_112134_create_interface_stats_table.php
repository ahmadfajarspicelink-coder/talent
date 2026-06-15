<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interface_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interface_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('in_octets');       // bytes masuk
            $table->unsignedBigInteger('out_octets');      // bytes keluar
            $table->unsignedBigInteger('in_errors')->default(0);
            $table->unsignedBigInteger('out_errors')->default(0);
            $table->enum('oper_status', ['up', 'down', 'testing', 'unknown']);
            $table->timestamp('polled_at');

            $table->index(['interface_id', 'polled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interface_stats');
    }
};
