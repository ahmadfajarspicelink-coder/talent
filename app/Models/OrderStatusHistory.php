<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Dokumen-dokumen yang dilampirkan pada entri riwayat ini.
     *
     * Satu tahap (history) dapat memiliki banyak OrderDocument — lihat
     * App\Models\OrderDocument. Batas "maks 5 dokumen per tahap" ditegakkan
     * di OrderDocumentController::store.
     *
     * @return HasMany<OrderDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(OrderDocument::class)->orderBy('id');
    }

    /**
     * Apakah entri riwayat ini memiliki setidaknya satu dokumen terlampir.
     */
    public function hasDocument(): bool
    {
        return $this->documents()->exists();
    }

    /**
     * Banyaknya dokumen terlampir.
     */
    public function getDocumentsCountAttribute(): int
    {
        return $this->documents()->count();
    }

    /**
     * Untuk backward-compat dengan view lama: URL dokumen pertama (atau null).
     */
    public function getDocumentUrlAttribute(): ?string
    {
        $first = $this->documents()->first();

        return $first?->document_url;
    }

    /**
     * Untuk backward-compat dengan view lama: nama dokumen pertama (atau null).
     */
    public function getDocumentNameAttribute(): ?string
    {
        return $this->documents()->first()?->document_name;
    }

    /**
     * Untuk backward-compat dengan view lama: path dokumen pertama (atau null).
     */
    public function getDocumentPathAttribute(): ?string
    {
        return $this->documents()->first()?->document_path;
    }

    /**
     * Ekstensi dokumen pertama — dipakai view lama (OrderController::show,
     * TrackingClientController) yang merender 1 dokumen per tahap. Tetap
     * dipertahankan untuk kompatibilitas; UI multi-dokumen sebaiknya
     * iterasi $history->documents langsung.
     */
    public function documentExtension(): ?string
    {
        return $this->documents()->first()?->documentExtension();
    }

    public function isPdf(): bool
    {
        return $this->documents()->first()?->isPdf() ?? false;
    }

    public function isImage(): bool
    {
        return $this->documents()->first()?->isImage() ?? false;
    }

    public function isPreviewable(): bool
    {
        return $this->documents()->first()?->isPreviewable() ?? false;
    }
}