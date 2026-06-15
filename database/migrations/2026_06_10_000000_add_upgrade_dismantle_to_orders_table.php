<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dukungan Upgrade & Dismantle layanan client.
 *
 * - parent_order_id: menautkan Order upgrade ke Order asal (revision chain).
 *   Order asal yang sudah di-upgrade disembunyikan dari daftar (R: order lama
 *   ter-upgrade disembunyikan).
 * - order_type: 'new' (default) atau 'upgrade'.
 * - dismantled_at: waktu layanan dibongkar. Status Order menjadi 'dismantled'
 *   (kolom status sudah VARCHAR(50) sehingga menampung nilai baru ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_order_id')->nullable()->after('id');
            $table->string('order_type', 20)->default('new')->after('order_number');
            $table->timestamp('dismantled_at')->nullable()->after('contract_end_date');

            $table->foreign('parent_order_id')
                ->references('id')->on('orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['parent_order_id']);
            $table->dropColumn(['parent_order_id', 'order_type', 'dismantled_at']);
        });
    }
};
