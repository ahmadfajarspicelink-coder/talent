<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M-04 (audit) — QW #9: perbesar kolom `snmp_community` untuk menyimpan
 * data encrypted.
 *
 * Encrypted string (base64 JSON) jauh lebih panjang dari plaintext.
 * VARCHAR(255) tidak cukup untuk menampung hasil Crypt::encryptString().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            // TEXT cukup untuk menampung encrypted payload Laravel (mac + iv + value).
            $table->text('snmp_community')->change();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            // Rollback ke VARCHAR(255) — data mungkin truncate jika ada value encrypted.
            $table->string('snmp_community', 255)->change();
        });
    }
};
