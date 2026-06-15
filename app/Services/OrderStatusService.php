<?php

namespace App\Services;

/**
 * Logika domain murni untuk alur Status_Order (Requirement 6).
 *
 * State machine 7 tahap berurutan: transisi hanya sah ke penerus langsung,
 * dan menghitung Persentase_Progress sebagai fungsi dari Status_Order.
 * Kelas ini tidak menyentuh HTTP/DB agar dapat diuji terisolasi.
 */
class OrderStatusService
{
    /**
     * Urutan tetap Status_Order (R6.1), indeks 0..6.
     *
     * @var list<string>
     */
    public const STATUSES = [
        'Inquiry',
        'Cek_Ketersediaan',
        'Penawaran',
        'PO_Provider',
        'PO_Vendor',
        'Instalasi',
        'BAA_BAST',
        'BAST_Vendor',
        'Client_Aktif',
    ];

    /**
     * Status terakhir (final) pada alur. Mencapai status ini menandakan order
     * selesai dan client menjadi aktif.
     */
    public const FINAL_STATUS = 'Client_Aktif';

    /**
     * Status terminal di luar alur 9 tahap: layanan telah dibongkar
     * (dismantle). Order dengan status ini tidak lagi dapat dimajukan dan
     * client-nya keluar dari daftar aktif.
     */
    public const DISMANTLED_STATUS = 'dismantled';

    /**
     * Status awal Order upgrade. Upgrade melewati Inquiry & Cek Ketersediaan
     * (layanan sudah ada), sehingga langkah pertama yang diisi adalah
     * Penawaran. Order dimulai di Cek_Ketersediaan agar penerus langsungnya
     * adalah Penawaran.
     */
    public const UPGRADE_START_STATUS = 'Cek_Ketersediaan';

    /**
     * Metadata tampilan tiap tahap (judul ramah + deskripsi singkat) untuk
     * UI "Alur Pemesanan". Tidak memengaruhi logika state machine.
     *
     * @var array<string, array{title: string, description: string}>
     */
    public const STAGE_META = [
        'Inquiry' => ['title' => 'Inquiry Ketersediaan', 'description' => 'Provider menanyakan ketersediaan jaringan'],
        'Cek_Ketersediaan' => ['title' => 'Cek Ketersediaan', 'description' => 'Konfirmasi ketersediaan jaringan'],
        'Penawaran' => ['title' => 'Penawaran ke Provider', 'description' => 'Mengajukan harga ke provider'],
        'PO_Provider' => ['title' => 'PO dari Provider', 'description' => 'Menerima PO dari provider'],
        'PO_Vendor' => ['title' => 'PO ke Vendor', 'description' => 'Menerbitkan PO ke vendor'],
        'Instalasi' => ['title' => 'Instalasi', 'description' => 'Vendor melakukan instalasi'],
        'BAA_BAST' => ['title' => 'BAA & BAST ke Provider', 'description' => 'Pengujian & BAA/BAST ke provider'],
        'BAST_Vendor' => ['title' => 'BAST dari Vendor', 'description' => 'BAST vendor diterima & diteruskan'],
        'Client_Aktif' => ['title' => 'Client Aktif', 'description' => 'Layanan client aktif & kontrak mulai'],
        'dismantled' => ['title' => 'Dismantled', 'description' => 'Layanan telah dibongkar'],
    ];

    /**
     * Judul ramah untuk sebuah status (fallback ke status mentah).
     */
    public function title(string $status): string
    {
        return self::STAGE_META[$status]['title'] ?? $status;
    }

    /**
     * Deskripsi singkat sebuah status (kosong bila tidak ada).
     */
    public function description(string $status): string
    {
        return self::STAGE_META[$status]['description'] ?? '';
    }

    /**
     * Indeks sebuah status pada urutan, atau -1 bila tidak dikenal.
     */
    public function indexOf(string $status): int
    {
        $index = array_search($status, self::STATUSES, true);

        return $index === false ? -1 : $index;
    }

    /**
     * Kembalikan tahap berurutan persis setelah $current, atau null jika
     * $current adalah Complete (tanpa penerus) atau bukan status valid.
     */
    public function nextStatus(string $current): ?string
    {
        $index = array_search($current, self::STATUSES, true);

        if ($index === false || $index >= count(self::STATUSES) - 1) {
            return null;
        }

        return self::STATUSES[$index + 1];
    }

    /**
     * Transisi sah jika dan hanya jika $target adalah penerus langsung
     * dari $current. Melompat, mundur, atau transisi apa pun dari Complete
     * selalu ditolak (R6.2, R6.3, R6.6).
     */
    public function canTransition(string $current, string $target): bool
    {
        return $this->nextStatus($current) === $target;
    }

    /**
     * Persentase_Progress = (indeks status pada urutan) / 6 * 100 (R6.7).
     * Inquiry = 0.0, Complete = 100.0. Status tak dikenal menghasilkan 0.0.
     */
    public function progressPercent(string $status): float
    {
        $index = array_search($status, self::STATUSES, true);

        if ($index === false) {
            return 0.0;
        }

        return $index / (count(self::STATUSES) - 1) * 100;
    }

    /**
     * True jika $status adalah tahap akhir (Client_Aktif).
     */
    public function isComplete(string $status): bool
    {
        return $status === self::FINAL_STATUS;
    }

    /**
     * True jika $status adalah status terminal dismantle.
     */
    public function isDismantled(string $status): bool
    {
        return $status === self::DISMANTLED_STATUS;
    }
}
