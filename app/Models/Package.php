<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Paket Internet — master data (id + nama) yang menjadi rujukan Order.
 */
class Package extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Order yang memakai paket ini.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
