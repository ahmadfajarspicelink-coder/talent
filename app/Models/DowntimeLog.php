<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DowntimeLog - satu entri insiden downtime client (Modul Ticket / Logdown).
 *
 * Mencatat waktu mulai down, waktu pulih (nullable selama masih down),
 * durasi turunan, vendor yang dipakai, client terkait, alasan, dan
 * tindakan perbaikan. status turunan: 'down' (insiden aktif) atau
 * 'up' (sudah pulih).
 */
class DowntimeLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'client_id',
        'client_name',
        'down_at',
        'up_at',
        'duration_seconds',
        'status',
        'reason',
        'action',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'down_at' => 'datetime',
            'up_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * Vendor Partner (type=vendor) yang dipakai saat insiden.
     *
     * @return BelongsTo<Partner, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    /**
     * Client terkait (clients.id). Nullable di skema; bila null, gunakan
     * kolom client_name sebagai tampilan.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * User yang membuat entri ini (audit H-04).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Label tampilan untuk kolom "Client": nama dari relasi bila ada,
     * fallback ke client_name, atau em-dash bila keduanya kosong.
     */
    public function getClientLabelAttribute(): string
    {
        return $this->client?->name
            ?: ($this->client_name ?: '—');
    }

    /**
     * Apakah insiden ini sudah pulih (up_at terisi).
     */
    public function getIsResolvedAttribute(): bool
    {
        return $this->up_at !== null;
    }

    /**
     * Durasi dalam format human-readable (mis. "2 jam 15 menit" atau
     * "45 detik"). Mengembalikan null bila insiden masih aktif.
     */
    public function getDurationHumanAttribute(): ?string
    {
        if ($this->duration_seconds === null) {
            return null;
        }

        $seconds = (int) $this->duration_seconds;

        if ($seconds < 60) {
            return $seconds.' detik';
        }

        $minutes = intdiv($seconds, 60);
        $remainSeconds = $seconds % 60;

        if ($minutes < 60) {
            $label = $minutes.' menit';

            return $remainSeconds > 0 ? $label.' '.$remainSeconds.' detik' : $label;
        }

        $hours = intdiv($minutes, 60);
        $remainMinutes = $minutes % 60;

        $label = $hours.' jam';

        return $remainMinutes > 0 ? $label.' '.$remainMinutes.' menit' : $label;
    }

    /**
     * Scope: hanya insiden yang masih aktif (belum pulih).
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'down');
    }

    /**
     * Scope: hanya insiden yang sudah pulih.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'up');
    }
}