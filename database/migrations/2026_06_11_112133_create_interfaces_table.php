<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->integer('if_index');         // SNMP ifIndex
            $table->string('if_name');           // e.g. "ether1", "Gi0/1"
            $table->string('if_descr')->nullable();  // ifDescr
            $table->string('if_alias')->nullable();  // ifAlias (description)
            $table->integer('if_speed')->nullable();  // ifSpeed (bps)
            $table->string('if_type')->nullable();    // ethernet, fiber, etc
            $table->enum('if_oper_status', ['up', 'down', 'testing', 'unknown'])->default('unknown');
            $table->enum('if_admin_status', ['up', 'down', 'testing', 'unknown'])->default('unknown');
            $table->unsignedBigInteger('if_in_octets')->default(0);   // counter32/64
            $table->unsignedBigInteger('if_out_octets')->default(0);
            $table->unsignedBigInteger('if_in_errors')->default(0);
            $table->unsignedBigInteger('if_out_errors')->default(0);
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'if_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interfaces');
    }
};
