<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Refactor: dokumen Order yang sebelumnya 1:1 pada kolom
     * `order_status_histories.document_path` / `document_name` dipindah ke
     * tabel terdedikasi `order_documents` agar satu tahap (history) dapat
     * memiliki banyak berkas (maks 5, @see OrderDocumentController::store).
     *
     * Setiap baris order_documents = 1 berkas fisik (path + nama asli +
     * ukuran + mime type untuk audit). Batas "maks 5 per tahap" ditegakkan
     * di level aplikasi.
     */
    public function up(): void
    {
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_status_history_id')
                ->constrained('order_status_histories')
                ->cascadeOnDelete();
            $table->string('document_path');
            $table->string('document_name');
            $table->unsignedInteger('size')->default(0); // bytes
            $table->string('mime_type', 127)->nullable();
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('order_status_history_id');
        });

        // Hapus kolom dokumen lama di order_status_histories — kini
        // didelegasikan ke tabel order_documents. Data historis di kolom
        // lama (jika ada) tidak dimigrasikan karena asumsinya kosong
        // (fitur multi-dokumen baru aktif setelah deployment migrasi ini).
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropColumn(['document_path', 'document_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
        });

        Schema::dropIfExists('order_documents');
    }
};