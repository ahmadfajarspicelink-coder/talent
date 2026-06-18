<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas dokumen individual yang dilampirkan pada sebuah entri riwayat
 * status Order (OrderStatusHistory). Satu history dapat memiliki banyak
 * OrderDocument (maks 5 — ditegakkan di OrderDocumentController::store).
 *
 * Tabel ini menggantikan kolom lama `order_status_histories.document_path`
 * & `document_name` agar setiap tahap dapat menyimpan multi-dokumen
 * dengan audit trail (uploaded_by, timestamps, size, mime_type).
 */
class OrderDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_status_history_id',
        'document_path',
        'document_name',
        'size',
        'mime_type',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Riwayat status tempat dokumen ini dilampirkan.
     *
     * @return BelongsTo<OrderStatusHistory, $this>
     */
    public function history(): BelongsTo
    {
        return $this->belongsTo(OrderStatusHistory::class, 'order_status_history_id');
    }

    /**
     * User yang mengunggah dokumen (null bila user sudah dihapus).
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * URL publik dokumen (atau null jika path kosong).
     */
    public function getDocumentUrlAttribute(): ?string
    {
        return $this->document_path
            ? Storage::disk('public')->url($this->document_path)
            : null;
    }

    /**
     * Ekstensi file (lowercase) — pakai nama asli atau fallback ke path.
     */
    public function documentExtension(): ?string
    {
        $name = $this->document_name ?: $this->document_path;

        return $name ? strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: null : null;
    }

    public function isPdf(): bool
    {
        return $this->documentExtension() === 'pdf';
    }

    public function isImage(): bool
    {
        return in_array($this->documentExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
    }

    public function isPreviewable(): bool
    {
        return $this->isPdf() || $this->isImage();
    }

    /**
     * Ukuran dalam MB (1 desimal), untuk tampilan UI.
     */
    public function getSizeMbAttribute(): ?string
    {
        return $this->size ? number_format($this->size / (1024 * 1024), 1) : null;
    }
}