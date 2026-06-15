<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Catatan/referensi singkat untuk sebuah tahap status (mis. nomor PO dari
     * provider pada tahap PO_Provider), agar referensi terlihat tanpa membuka
     * berkas dokumen.
     */
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('note')->nullable()->after('document_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
