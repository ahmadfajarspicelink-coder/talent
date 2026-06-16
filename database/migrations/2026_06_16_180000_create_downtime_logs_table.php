<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel downtime_logs menyimpan catatan downtime client aktif yang
 * dipakai oleh Modul Ticket → sub-modul Logdown. Setiap entri
 * merepresentasikan satu insiden: kapan mulai down, kapan pulih,
 * vendor yang dipakai, client terkait, alasan, dan tindakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downtime_logs', function (Blueprint $table) {
            $table->id();

            // Vendor yang dipakai saat insiden (FK ke partners, type=vendor).
            // Nullable: kolom client_name dapat diisi manual bila vendor
            // tidak/belum tercatat di master Partner.
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('partners')
                ->nullOnDelete();

            // Client aktif (FK ke clients). Nullable agar logdown juga
            // dapat mencatat insiden di luar daftar client aktif.
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            // Fallback nama client (atau nama lokasi/site) ketika bukan
            // client yang tercatat di tabel clients.
            $table->string('client_name')->nullable();

            // Waktu mulai down dan waktu pulih (datetime penuh = tgl+jam).
            $table->dateTime('down_at');
            $table->dateTime('up_at')->nullable();

            // Durasi downtime dalam detik. Disimpan agar tidak perlu hitung
            // ulang di tiap render dan untuk memudahkan agregasi/laporan.
            $table->unsignedInteger('duration_seconds')->nullable();

            // Status turunan: 'down' (masih insiden) atau 'up' (sudah pulih).
            $table->string('status')->default('down');

            // Narasi penyebab dan tindakan yang dilakukan.
            $table->text('reason');
            $table->text('action')->nullable();

            // Audit: siapa yang membuat entri.
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Index untuk query daftar insiden yang masih 'down' dan
            // untuk pengurutan kronologis.
            $table->index(['status', 'down_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downtime_logs');
    }
};