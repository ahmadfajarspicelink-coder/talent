<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interfaces', function (Blueprint $table) {
            $table->unsignedBigInteger('if_speed')->change();
        });
    }

    public function down(): void
    {
        Schema::table('interfaces', function (Blueprint $table) {
            $table->unsignedInteger('if_speed')->change();
        });
    }
};
