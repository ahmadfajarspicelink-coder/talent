<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // Nama perangkat (e.g. "Switch Lt.2")
            $table->string('ip_address');        // IP perangkat
            $table->string('snmp_community')->default('public'); // SNMP community string
            $table->string('snmp_version')->default('2c');       // 1, 2c, 3
            $table->string('vendor')->nullable();   // mikrotik, cisco, huawei, etc
            $table->string('model')->nullable();    // model perangkat
            $table->string('location')->nullable(); // lokasi fisik
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown');
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
