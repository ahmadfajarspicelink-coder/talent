<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Modul_Client bersifat read-only dan relation-driven.
 *
 * Client TIDAK dibuat manual di sini, melainkan terbentuk dari Modul_Order
 * (nama client diisi saat membuat Order). Sebuah Client baru muncul di daftar
 * ini hanya setelah salah satu Order miliknya mencapai status Complete —
 * saat itu OrderObserver menandai client berstatus 'active' (R4.5). Karena
 * itu daftar di bawah hanya menampilkan client berstatus aktif.
 *
 * Modul ini juga menjadi titik aksi Upgrade & Dismantle layanan:
 *  - Upgrade  : membuka Order upgrade baru (revision chain) yang dimulai dari
 *               tahap Penawaran, lalu mengarahkan ke halaman Order tersebut.
 *  - Dismantle: menandai Order aktif client menjadi 'dismantled' dan
 *               mengeluarkan client dari daftar aktif (tersimpan sebagai
 *               riwayat dismantle).
 */
class ClientController extends Controller
{
    /**
     * Tampilkan daftar Client aktif + riwayat dismantle.
     */
    public function index(): View
    {
        $clients = Client::where('status', 'active')
            ->with([
                'latestCompletedOrder.provider',
                'latestCompletedOrder.vendor',
                'latestCompletedOrder.package',
            ])
            ->orderBy('name')
            ->get();

        // Riwayat dismantle: Order yang layanannya telah dibongkar.
        $dismantled = Order::where('status', OrderStatusService::DISMANTLED_STATUS)
            ->with(['client', 'provider', 'vendor'])
            ->orderByDesc('dismantled_at')
            ->get();

        return view('clients.index', [
            'clients' => $clients,
            'dismantled' => $dismantled,
        ]);
    }

    /**
     * Mulai proses Upgrade layanan sebuah Client.
     *
     * Membuat Order upgrade baru yang menautkan Order aktif terakhir client
     * (parent), menyalin struktur (provider/vendor/paket) dan harga lama
     * sebagai acuan, lalu memulai alur dari Cek_Ketersediaan agar langkah
     * pertama yang diisi adalah Penawaran. Order asal tetap tersimpan namun
     * disembunyikan dari daftar karena kini punya turunan.
     */
    public function upgrade(Client $client): RedirectResponse
    {
        $parent = $client->latestCompletedOrder;

        if ($parent === null) {
            return redirect()
                ->route('clients.index')
                ->with('error', 'Client belum memiliki layanan aktif untuk di-upgrade.');
        }

        $order = DB::transaction(function () use ($client, $parent): Order {
            $order = Order::create([
                'client_id' => $client->id,
                'parent_order_id' => $parent->id,
                'order_type' => 'upgrade',
                'provider_id' => $parent->provider_id,
                'vendor_id' => $parent->vendor_id,
                'package_id' => $parent->package_id,
                'package_name' => $parent->package_name,
                // Spesifikasi & harga lama sebagai acuan; akan disesuaikan di alur upgrade.
                'bandwidth' => $parent->bandwidth,
                'provider_otc' => $parent->provider_otc,
                'provider_mrc' => $parent->provider_mrc,
                'vendor_otc' => $parent->vendor_otc,
                'vendor_mrc' => $parent->vendor_mrc,
                'note' => 'Upgrade dari '.$parent->display_number,
                'status' => OrderStatusService::UPGRADE_START_STATUS,
            ]);

            $order->update([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.bin2hex(random_bytes(3)),
            ]);

            $order->statusHistories()->create([
                'status' => OrderStatusService::UPGRADE_START_STATUS,
                'note' => 'Order upgrade dibuka dari '.$parent->display_number,
                'changed_at' => now(),
            ]);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Order upgrade dibuka. Lanjutkan dari tahap Penawaran.');
    }

    /**
     * Bongkar (dismantle) layanan aktif sebuah Client.
     *
     * Order aktif terakhir client ditandai 'dismantled' dan client diset
     * 'inactive' sehingga keluar dari daftar aktif. Order tetap tersimpan
     * sebagai riwayat dismantle.
     */
    public function dismantle(Client $client): RedirectResponse
    {
        $order = $client->latestCompletedOrder;

        if ($order === null) {
            return redirect()
                ->route('clients.index')
                ->with('error', 'Client tidak memiliki layanan aktif untuk dibongkar.');
        }

        DB::transaction(function () use ($client, $order): void {
            $order->update([
                'status' => OrderStatusService::DISMANTLED_STATUS,
                'dismantled_at' => now(),
            ]);

            $client->update(['status' => 'inactive']);
        });

        return redirect()
            ->route('clients.index')
            ->with('status', 'Layanan '.$client->name.' telah dibongkar (dismantle).');
    }
}
