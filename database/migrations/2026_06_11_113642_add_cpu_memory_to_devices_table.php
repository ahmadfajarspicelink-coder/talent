<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedTinyInteger('cpu_usage')->nullable()->after('status');
            $table->unsignedBigInteger('memory_used')->nullable()->after('cpu_usage');
            $table->unsignedBigInteger('memory_total')->nullable()->after('memory_used');
            $table->unsignedInteger('uptime_ticks')->nullable()->after('memory_total');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['cpu_usage', 'memory_used', 'memory_total', 'uptime_ticks']);
        });
    }
};
