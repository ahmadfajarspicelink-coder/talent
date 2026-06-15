<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan detail layanan pada Order: nama paket dan jumlah bandwidth.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('package_name')->nullable()->after('vendor_id');
            $table->string('bandwidth')->nullable()->after('package_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['package_name', 'bandwidth']);
        });
    }
};
