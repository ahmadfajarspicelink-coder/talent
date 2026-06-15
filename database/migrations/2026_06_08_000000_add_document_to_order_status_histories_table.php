<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom dokumen pendukung pada tiap entri riwayat status.
     *
     * Setiap perubahan Status_Order (mis. Penawaran, PO_Provider, PO_Vendor,
     * Instalasi, BAA_BAST) dapat menyertakan dokumen yang diunggah. Path file
     * disimpan relatif terhadap disk `public`, beserta nama asli file untuk
     * ditampilkan di riwayat status.
     */
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('status');
            $table->string('document_name')->nullable()->after('document_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropColumn(['document_path', 'document_name']);
        });
    }
};
