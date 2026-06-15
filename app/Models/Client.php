<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'status',
    ];

    /**
     * Order milik Client ini (orders.client_id).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    /**
     * Order terakhir milik Client ini yang berstatus Complete.
     *
     * Dipakai Modul_Client untuk menampilkan nama paket & bandwidth dari
     * order yang membuat Client menjadi aktif (R4.5).
     *
     * @return HasOne<Order, $this>
     */
    public function latestCompletedOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'client_id')
            ->where('status', 'Client_Aktif')
            ->latestOfMany();
    }
}
