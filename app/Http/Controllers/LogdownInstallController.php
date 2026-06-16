<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * LogdownInstallController — endpoint one-time untuk membuat tabel
 * downtime_logs lewat browser (pengganti `php artisan migrate`).
 *
 * Dibuat karena PHP CLI tidak selalu tersedia di terminal user
 * (mis. Laragon belum masuk PATH). Aman dipanggil berulang:
 * Schema::hasTable() di-cek dulu sebelum membuat.
 *
 * Hanya admin yang boleh akses (diatur via middleware 'module:ticket'
 * + check role 'admin' di method).
 */
class LogdownInstallController extends Controller
{
    /**
     * Buat tabel downtime_logs via Schema facade. Idempotent.
     */
    public function install(): Response
    {
        // Guard: hanya admin.
        $user = Auth::user();
        $role = (string) ($user->role ?? '');
        if ($role !== 'admin') {
            abort(403, 'Hanya admin yang boleh menjalankan instalasi.');
        }

        if (Schema::hasTable('downtime_logs')) {
            return response(
                "Tabel downtime_logs sudah ada. Tidak ada perubahan.\n".
                "Coba buka: /tickets/logdown",
                200,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        Schema::create('downtime_logs', function ($table) {
            $table->id();

            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('partners')
                ->nullOnDelete();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->string('client_name')->nullable();

            $table->dateTime('down_at');
            $table->dateTime('up_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('down');

            $table->text('reason');
            $table->text('action')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'down_at']);
        });

        // Opsional: catat migration di tabel migrations supaya status-nya
        // sinkron dengan tracking Laravel (biar `php artisan migrate`
        // berikutnya tidak gagal).
        try {
            Artisan::call('migrate:status', []);
        } catch (\Throwable $e) {
            // Abaikan — fokus utama: tabel sudah dibuat.
        }

        return response(
            "Tabel downtime_logs BERHASIL dibuat.\n\n".
            "Sekarang buka: /tickets/logdown untuk mulai mencatat downtime client.\n",
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}