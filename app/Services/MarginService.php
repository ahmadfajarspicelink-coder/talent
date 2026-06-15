<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Logika domain murni untuk perhitungan margin keuangan (Requirement 7).
 *
 * Margin dihitung sebagai selisih Harga_Provider - Harga_Vendor untuk
 * komponen OTC dan MRC. Kelas ini membedakan margin "tidak tersedia"
 * (harga belum lengkap -> null) dari nol hasil perhitungan (R7.5, R7.6).
 *
 * Kelas tidak menyentuh HTTP/DB agar dapat diuji terisolasi. Order dan
 * Client cukup berupa objek apa pun yang mengekspos properti harga
 * (provider_otc, provider_mrc, vendor_otc, vendor_mrc) dan, untuk client,
 * sebuah koleksi/iterable order yang dapat diakses lewat properti `orders`.
 */
class MarginService
{
    /**
     * Margin_OTC = Harga_Provider OTC - Harga_Vendor OTC (R7.1).
     *
     * Mengembalikan null jika salah satu komponen OTC belum terisi,
     * untuk membedakan "tidak tersedia" dari nol hasil hitung (R7.5).
     *
     * @param  object  $order  objek dengan properti provider_otc & vendor_otc
     */
    public function marginOtc(object $order): ?int
    {
        $provider = $this->priceOf($order, 'provider_otc');
        $vendor = $this->priceOf($order, 'vendor_otc');

        if ($provider === null || $vendor === null) {
            return null;
        }

        return $provider - $vendor;
    }

    /**
     * Margin_MRC = Harga_Provider MRC - Harga_Vendor MRC (R7.2).
     *
     * Mengembalikan null jika salah satu komponen MRC belum terisi (R7.5).
     *
     * @param  object  $order  objek dengan properti provider_mrc & vendor_mrc
     */
    public function marginMrc(object $order): ?int
    {
        $provider = $this->priceOf($order, 'provider_mrc');
        $vendor = $this->priceOf($order, 'vendor_mrc');

        if ($provider === null || $vendor === null) {
            return null;
        }

        return $provider - $vendor;
    }

    /**
     * Bangun baris laporan margin per Order (R7.3, R7.5, R7.6).
     *
     * Setiap baris memuat order asli, margin OTC, margin MRC, dan flag
     * `available`. Flag bernilai true hanya ketika KEEMPAT harga lengkap,
     * sehingga membedakan "tidak tersedia" dari nol hasil hitung.
     *
     * @param  iterable<object>  $orders
     * @return Collection<int, array{order: object, otc: ?int, mrc: ?int, total: ?int, available: bool}>
     */
    public function orderMargins(iterable $orders): Collection
    {
        $rows = new Collection();

        foreach ($orders as $order) {
            $otc = $this->marginOtc($order);
            $mrc = $this->marginMrc($order);
            $available = $otc !== null && $mrc !== null;

            $rows->push([
                'order' => $order,
                'otc' => $otc,
                'mrc' => $mrc,
                'total' => $available ? $otc + $mrc : null,
                'available' => $available,
            ]);
        }

        return $rows;
    }

    /**
     * Total_Margin_Per_Client: agregasi Margin_OTC & Margin_MRC dari seluruh
     * Order milik Client yang harganya lengkap (R7.4).
     *
     * Order dengan harga tidak lengkap diabaikan dari agregasi. Jika tidak
     * ada satu pun Order berharga lengkap, kedua nilai bernilai null untuk
     * menandai "tidak tersedia" alih-alih nol hasil hitung.
     *
     * @param  object  $client  objek dengan properti/koleksi `orders`
     * @return array{otc: ?int, mrc: ?int}
     */
    public function totalMarginPerClient(object $client): array
    {
        $otcTotal = null;
        $mrcTotal = null;

        foreach ($this->ordersOf($client) as $order) {
            $otc = $this->marginOtc($order);
            $mrc = $this->marginMrc($order);

            // Hanya agregasi order berharga lengkap (kedua margin tersedia).
            if ($otc === null || $mrc === null) {
                continue;
            }

            $otcTotal = ($otcTotal ?? 0) + $otc;
            $mrcTotal = ($mrcTotal ?? 0) + $mrc;
        }

        return ['otc' => $otcTotal, 'mrc' => $mrcTotal];
    }

    /**
     * Baca komponen harga dari order secara dinamis dan normalkan ke ?int.
     * Nilai null (harga belum diisi) dipertahankan sebagai null.
     */
    private function priceOf(object $order, string $key): ?int
    {
        $value = $order->{$key} ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * Ambil koleksi/iterable Order milik sebuah Client secara dinamis.
     *
     * @return iterable<object>
     */
    private function ordersOf(object $client): iterable
    {
        $orders = $client->orders ?? [];

        return is_iterable($orders) ? $orders : [];
    }
}
