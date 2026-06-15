<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order kini merujuk Paket Internet via package_id (dropdown), selain
     * kolom legacy package_name yang dipertahankan untuk data lama.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('package_name')
                ->constrained('packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });
    }
};
