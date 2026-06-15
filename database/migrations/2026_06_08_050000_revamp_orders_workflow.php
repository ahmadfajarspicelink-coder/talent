<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Revamp alur Order menjadi 9 tahap dengan data per-tahap yang tersimpan pada
 * Order (nomor penawaran/PO/BAA/BAST, harga, bandwidth, dan kontrak client).
 *
 * - status: enum 7-tahap -> VARCHAR (mendukung 9 tahap baru). Memakai
 *   Schema::change() agar lintas-database (MySQL & SQLite untuk test).
 * - vendor_id: kini opsional (boleh null saat pembuatan order).
 * - kolom baru: catatan order + nomor referensi tiap tahap + data kontrak.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom baru.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('note')->nullable()->after('bandwidth');
            $table->string('offer_number')->nullable()->after('note');
            $table->string('po_provider_number')->nullable()->after('offer_number');
            $table->string('po_vendor_number')->nullable()->after('po_provider_number');
            $table->string('baa_number')->nullable()->after('po_vendor_number');
            $table->string('bast_number')->nullable()->after('baa_number');
            $table->unsignedInteger('contract_months')->nullable()->after('bast_number');
            $table->date('contract_start_date')->nullable()->after('contract_months');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
        });

        // Enum -> VARCHAR (mendukung tahap baru) + vendor opsional.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 50)->default('Inquiry')->change();
            $table->unsignedBigInteger('vendor_id')->nullable()->change();
        });

        // Riwayat status juga memakai enum 7-tahap -> ubah ke VARCHAR.
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('status', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'note',
                'offer_number',
                'po_provider_number',
                'po_vendor_number',
                'baa_number',
                'bast_number',
                'contract_months',
                'contract_start_date',
                'contract_end_date',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable(false)->change();
            $table->string('status', 50)->default('Inquiry')->change();
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('status', 50)->change();
        });
    }
};
