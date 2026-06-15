<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'address',
        'pic',
        'status',
    ];

    /**
     * Order yang merujuk Partner ini sebagai Provider (orders.provider_id).
     *
     * Catatan: sebuah Order punya DUA foreign key ke partners — provider_id
     * dan vendor_id. Relasi generik orders() memakai provider_id sebagai
     * default. Untuk mendeteksi keterkaitan Order secara menyeluruh (guard
     * penghapusan R3.7), gunakan hasLinkedOrders() yang memeriksa kedua FK.
     */
    public function orders(): HasMany
    {
        return $this->providerOrders();
    }

    /**
     * Order di mana Partner ini berperan sebagai Provider.
     */
    public function providerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'provider_id');
    }

    /**
     * Order di mana Partner ini berperan sebagai Vendor.
     */
    public function vendorOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }

    /**
     * Apakah Partner ini terhubung ke minimal satu Order, baik sebagai
     * Provider maupun Vendor. Dipakai untuk guard penghapusan (R3.6/R3.7).
     */
    public function hasLinkedOrders(): bool
    {
        return $this->providerOrders()->exists() || $this->vendorOrders()->exists();
    }
}
