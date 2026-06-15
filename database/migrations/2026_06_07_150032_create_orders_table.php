<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('provider_id')->constrained('partners');
            $table->foreignId('vendor_id')->constrained('partners');
            $table->enum('status', [
                'Inquiry',
                'Penawaran',
                'PO_Provider',
                'PO_Vendor',
                'Instalasi',
                'BAA_BAST',
                'Complete',
            ])->default('Inquiry');
            $table->integer('provider_otc')->nullable();
            $table->integer('provider_mrc')->nullable();
            $table->integer('vendor_otc')->nullable();
            $table->integer('vendor_mrc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
