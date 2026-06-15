<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderStatusHistory extends Model
{
    use HasFactory;

    /**
     * The table is append-only and has no created_at/updated_at columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'document_path',
        'document_name',
        'note',
        'changed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * The Order this status history entry belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Apakah entri riwayat ini memiliki dokumen terlampir.
     */
    public function hasDocument(): bool
    {
        return ! empty($this->document_path);
    }

    /**
     * URL publik dokumen terlampir (atau null jika tidak ada).
     */
    public function getDocumentUrlAttribute(): ?string
    {
        return $this->document_path
            ? Storage::disk('public')->url($this->document_path)
            : null;
    }

    /**
     * Ekstensi file dokumen (lowercase), atau null jika tidak ada dokumen.
     */
    public function documentExtension(): ?string
    {
        if (! $this->hasDocument()) {
            return null;
        }

        $name = $this->document_name ?: $this->document_path;

        return strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: null;
    }

    /**
     * Apakah dokumen berupa PDF (dapat ditampilkan di viewer browser).
     */
    public function isPdf(): bool
    {
        return $this->documentExtension() === 'pdf';
    }

    /**
     * Apakah dokumen berupa gambar (dapat ditampilkan langsung).
     */
    public function isImage(): bool
    {
        return in_array($this->documentExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
    }

    /**
     * Apakah dokumen dapat dipratinjau langsung di browser (PDF atau gambar).
     */
    public function isPreviewable(): bool
    {
        return $this->isPdf() || $this->isImage();
    }
}
