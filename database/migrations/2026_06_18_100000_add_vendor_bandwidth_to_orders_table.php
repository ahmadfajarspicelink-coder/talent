<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penambahan kolom `vendor_bandwidth` ke tabel `orders`.
 *
 * Latar belakang (H-05 — QW #6):
 *   Kolom `bandwidth` awalnya menampung nilai bandwidth yang disepakati pada
 *   tahap PO_Provider. Pada tahap PO_Vendor, nilai `bandwidth` sebelumnya
 *   ikut ditimpa lewat form (name="bandwidth") sehingga nilai PO_Provider
 *   hilang saat user submit PO_Vendor. Akibatnya:
 *     - ringkasan PO_Provider di timeline ikut berubah,
 *     - kolom Bandwidth di panel kanan ikut salah,
 *     - alert mismatch bandwidth tidak pernah aktif (karena nilai sumber
 *       sudah tertimpa nilai input).
 *
 *   Solusi: pisahkan menjadi dua kolom.
 *     - `bandwidth`      : nilai bandwidth dari PO_Provider (tidak berubah).
 *     - `vendor_bandwidth` : nilai bandwidth yang dinegosiasikan dengan
 *                            vendor pada tahap PO_Vendor. Boleh berbeda
 *                            dengan `bandwidth` (alert mismatch kuning
 *                            akan muncul di UI tetapi tidak memblokir
 *                            submit).
 *
 *   Migrasi ini pakai `Schema::table()` biasa (tanpa Schema::change()) agar
 *   tidak menambah dependensi doctrine/dbal, konsisten dengan migrasi revamp
 *   workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Disimpan sebagai string (sama seperti `bandwidth`) agar konsisten
            // dengan tipe kolom `bandwidth` yang sudah ada (lihat migrasi
            // 2026_06_08_010000_add_package_and_bandwidth_to_orders_table).
            $table->string('vendor_bandwidth')->nullable()->after('bandwidth');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('vendor_bandwidth');
        });
    }
};