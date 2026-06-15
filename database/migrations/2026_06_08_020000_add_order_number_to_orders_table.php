<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan nomor order yang dihasilkan otomatis (mis. ORD-000001).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->unique()->after('id');
        });

        // Backfill nomor order untuk data yang sudah ada (jika ada).
        foreach (DB::table('orders')->orderBy('id')->pluck('id') as $id) {
            DB::table('orders')
                ->where('id', $id)
                ->update(['order_number' => 'ORD-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_number');
        });
    }
};
