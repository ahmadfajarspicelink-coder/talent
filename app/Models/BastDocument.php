<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Dokumen BAA/BAST yang di-generate dari template untuk sebuah Order.
class BastDocument extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'document_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}