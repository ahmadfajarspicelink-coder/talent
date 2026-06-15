<?php

namespace App\Models;

use App\Observers\OrderObserver;
use App\Services\OrderStatusService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'parent_order_id',
        'provider_id',
        'vendor_id',
        'created_by',  // H-04 (audit): ownership tracking
        'order_number',
        'order_type',
        'package_id',
        'package_name',
        'bandwidth',
        'note',
        'status',
        'provider_otc',
        'provider_mrc',
        'vendor_otc',
        'vendor_mrc',
        'offer_number',
        'po_provider_number',
        'po_vendor_number',
        'baa_number',
        'bast_number',
        'contract_months',
        'contract_start_date',
        'contract_end_date',
        'dismantled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider_otc' => 'integer',
            'provider_mrc' => 'integer',
            'vendor_otc' => 'integer',
            'vendor_mrc' => 'integer',
            'contract_months' => 'integer',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'dismantled_at' => 'datetime',
        ];
    }

    /**
     * The Client that owns this Order.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The Provider Partner for this Order.
     *
     * @return BelongsTo<Partner, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'provider_id');
    }

    /**
     * The Vendor Partner for this Order.
     *
     * @return BelongsTo<Partner, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    /**
     * Paket Internet yang dipilih untuk Order ini.
     *
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * The User that created this Order (H-04 — QW #10).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The status change history for this Order.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Order asal yang di-upgrade oleh Order ini (revision chain).
     *
     * @return BelongsTo<Order, $this>
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_order_id');
    }

    /**
     * Order upgrade yang menggantikan Order ini.
     *
     * @return HasMany<Order, $this>
     */
    public function upgrades(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_order_id');
    }

    public function bastDocuments(): HasMany
    {
        return $this->hasMany(BastDocument::class);
    }

    /**
     * True bila Order ini sudah digantikan oleh Order upgrade (punya turunan),
     * sehingga disembunyikan dari daftar Order & laporan Finance.
     */
    public function getIsSupersededAttribute(): bool
    {
        return $this->upgrades()->exists();
    }

    /**
     * True bila Order ini adalah hasil upgrade dari Order lain.
     */
    public function getIsUpgradeAttribute(): bool
    {
        return $this->order_type === 'upgrade' || $this->parent_order_id !== null;
    }

    /**
     * True bila layanan Order ini telah dibongkar (dismantle).
     */
    public function getIsDismantledAttribute(): bool
    {
        return $this->status === OrderStatusService::DISMANTLED_STATUS;
    }

    /**
     * Nomor order yang ditampilkan. Memakai order_number tersimpan, atau
     * fallback berbasis ID bila belum diisi (mis. data lama/factory).
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->order_number
            ?: 'ORD-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Label nama paket: dari relasi Package bila ada, fallback ke kolom
     * legacy package_name.
     */
    public function getPackageLabelAttribute(): ?string
    {
        return $this->package?->name ?: ($this->package_name ?: null);
    }

    /**
     * Label bandwidth dengan satuan Mbps. Disimpan sebagai angka saja;
     * satuan ditambahkan saat ditampilkan. Nilai legacy yang sudah memuat
     * satuan dibiarkan apa adanya.
     */
    public function getBandwidthLabelAttribute(): ?string
    {
        $value = trim((string) $this->bandwidth);

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? $value.' Mbps' : $value;
    }

    /**
     * Margin OTC = Harga_Provider OTC - Harga_Vendor OTC.
     * Null bila salah satu komponen OTC belum diisi (tidak tersedia).
     */
    public function getMarginOtcAttribute(): ?int
    {
        if ($this->provider_otc === null || $this->vendor_otc === null) {
            return null;
        }

        return (int) $this->provider_otc - (int) $this->vendor_otc;
    }

    /**
     * Margin MRC = Harga_Provider MRC - Harga_Vendor MRC.
     * Null bila salah satu komponen MRC belum diisi (tidak tersedia).
     */
    public function getMarginMrcAttribute(): ?int
    {
        if ($this->provider_mrc === null || $this->vendor_mrc === null) {
            return null;
        }

        return (int) $this->provider_mrc - (int) $this->vendor_mrc;
    }

    /**
     * True bila order sudah selesai (Client_Aktif) dan kontrak masih berjalan
     * (tanggal hari ini belum melewati contract_end_date). Dipakai Modul_Client
     * untuk menentukan Status Kontrak.
     */
    public function getContractActiveAttribute(): bool
    {
        if ($this->status !== OrderStatusService::FINAL_STATUS) {
            return false;
        }

        // Tanpa tanggal akhir, kontrak dianggap masih aktif selama order selesai.
        if ($this->contract_end_date === null) {
            return true;
        }

        return $this->contract_end_date->endOfDay()->isFuture();
    }

    /**
     * Label Status Kontrak yang siap ditampilkan: "Aktif" atau "Tidak Aktif".
     */
    public function getContractStatusLabelAttribute(): string
    {
        return $this->contract_active ? 'Aktif' : 'Tidak Aktif';
    }
}
