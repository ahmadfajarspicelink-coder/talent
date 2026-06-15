<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H-04 (audit) — QW #10: tambah kolom `created_by` ke tabel `orders`
 * untuk mendukung ownership-based authorization.
 *
 * Sebelumnya OrderPolicy::view() / update() selalu return true (staff bisa
 * lihat & update SEMUA order). Fix ini: staff hanya bisa akses order yang
 * mereka create. Admin tetap akses semua.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Nullable: order lama (pre-feature) tidak punya creator.
            $table->foreignId('created_by')
                ->nullable()
                ->after('vendor_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
