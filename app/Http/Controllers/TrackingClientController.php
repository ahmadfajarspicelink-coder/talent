<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DowntimeLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\OrderStatusService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

/**
 * Modul Tracking Client — tampilan read-only riwayat lengkap sebuah client.
 *
 * Berbeda dari Modul_Client (yang hanya menampilkan identitas & aksi),
 * Tracking Client menggabungkan empat sumber data menjadi satu kronologi
 * terurut (timeline) untuk client aktif:
 *
 *   1. Order (Order + OrderStatusHistory)   — order awal & progres tahap.
 *   2. Logdown (DowntimeLog)                — catatan downtime / tiket.
 *   3. Upgrade (Order.order_type = upgrade, parent_order_id != null).
 *   4. Dismantle (Order.status = dismantled).
 *
 * Tampilan murni view-only: tidak ada form edit / upload / tombol lanjut
 * tahap. Setiap entri riwayat ditampilkan dengan "spotlight" warna berbeda
 * sesuai jenis aktivitas agar mata pembaca langsung mengenali konteks.
 */
class TrackingClientController extends Controller
{
    /**
     * Tampilkan Tracking Client untuk satu client aktif.
     *
     * Client diambil dari route parameter (clients.tracking). Daftar seluruh
     * client aktif juga diteruskan ke view untuk mendukung dropdown
     * navigasi cepat bila admin ingin berpindah antar client tanpa
     * meninggalkan halaman.
     */
    public function show(Client $client): View
    {
        // Eager load semua relasi yang ditampilkan di timeline, termasuk
        // dokumen per status history (OrderStatusHistory.documents) agar
        // view bisa menampilkan multi-dokumen per tahap tanpa N+1.
        $client->load([
            'orders' => fn ($q) => $q
                ->orderBy('created_at')
                ->with([
                    'provider',
                    'vendor',
                    'package',
                    'statusHistories' => fn ($q2) => $q2->orderBy('changed_at'),
                    'statusHistories.documents',
                ]),
            'orders.upgrades',
        ]);

        $logdowns = DowntimeLog::with('vendor')
            ->where('client_id', $client->id)
            ->orderBy('down_at')
            ->get();

        $timeline = collect($this->buildTimeline($client, $logdowns));

        // Daftar seluruh client aktif untuk navigasi dropdown.
        $activeClients = Client::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('clients.tracking', [
            'client' => $client,
            'orders' => $client->orders,
            'logdowns' => $logdowns,
            'timeline' => $timeline,
            'activeClients' => $activeClients,
            'statusService' => app(OrderStatusService::class),
        ]);
    }

    /**
     * Bangun timeline (kronologi) aktivitas client — gabungan empat sumber.
     *
     * Hasil: array berisi entri terurut naik (lama → baru). Tiap entri
     * membawa field seragam: type, subtype, date, title, description,
     * meta, icon, color (spotlight), link. View memilih render sesuai type.
     *
     * @return list<array<string, mixed>>
     */
    private function buildTimeline(Client $client, Collection $logdowns): array
    {
        $events = [];

        // 1. Order: event per order (created + status progression + dismantle/upgrade).
        foreach ($client->orders as $order) {
            // Event "Order Dibuat" — pertama kali Order tercatat.
            $events[] = $this->event(
                type: 'order',
                subtype: 'order_created',
                date: $order->created_at,
                title: 'Order Dibuat',
                description: $order->display_number.' — '.$order->provider?->name.' → '.$order->vendor?->name,
                meta: [
                    'order_id' => $order->id,
                    'order_number' => $order->display_number,
                    'order_type' => $order->order_type,
                    'parent_order_id' => $order->parent_order_id,
                ],
                icon: 'plus',
                color: 'blue',
            );

            // Event per perubahan status (OrderStatusHistory).
            foreach ($order->statusHistories as $history) {
                $documents = $this->documentsMeta($order, $history);

                $events[] = $this->event(
                    type: 'order',
                    subtype: 'status_change',
                    date: $history->changed_at,
                    title: app(OrderStatusService::class)->title($history->status),
                    description: $history->note
                        ?: ('Status berpindah ke '.$history->status),
                    meta: [
                        'order_id' => $order->id,
                        'order_number' => $order->display_number,
                        'status' => $history->status,
                        'has_document' => $documents !== [],
                        'documents' => $documents,
                        'note' => $history->note,
                    ],
                    icon: $this->statusIcon($history->status),
                    color: $this->statusColor($history->status),
                );
            }

            // Event Upgrade: bila order ini punya parent (order_type=upgrade).
            if ($order->is_upgrade && $order->parentOrder) {
                $events[] = $this->event(
                    type: 'upgrade',
                    subtype: 'upgrade_created',
                    date: $order->created_at,
                    title: 'Upgrade Bandwidth',
                    description: 'Upgrade dari '.$order->parentOrder->display_number.
                        ' — layanan sebelumnya '.$order->parentOrder->bandwidth_label.' / MRC '.
                        number_format((int) $order->parentOrder->provider_mrc, 0, ',', '.'),
                    meta: [
                        'order_id' => $order->id,
                        'order_number' => $order->display_number,
                        'parent_order_id' => $order->parent_order_id,
                        'old_bandwidth' => $order->parentOrder->bandwidth_label,
                        'old_mrc' => $order->parentOrder->provider_mrc,
                    ],
                    icon: 'arrow-up',
                    color: 'indigo',
                );
            }

            // Event Dismantle: order ini dibongkar.
            if ($order->is_dismantled && $order->dismantled_at) {
                $events[] = $this->event(
                    type: 'dismantle',
                    subtype: 'dismantled',
                    date: $order->dismantled_at,
                    title: 'Dismantle (Layanan Dibongkar)',
                    description: 'Layanan '.$order->display_number.' telah dibongkar dan client keluar dari daftar aktif.',
                    meta: [
                        'order_id' => $order->id,
                        'order_number' => $order->display_number,
                    ],
                    icon: 'x-circle',
                    color: 'red',
                );
            }
        }

        // 2. Logdown: catatan downtime client ini.
        foreach ($logdowns as $log) {
            $isResolved = $log->up_at !== null;

            $events[] = $this->event(
                type: 'logdown',
                subtype: $isResolved ? 'logdown_resolved' : 'logdown_ongoing',
                date: $log->down_at,
                title: $isResolved ? 'Logdown (Sudah Pulih)' : 'Logdown (Masih Down)',
                description: $log->reason ?: 'Downtime tercatat pada sistem.',
                meta: [
                    'logdown_id' => $log->id,
                    'vendor' => $log->vendor?->name,
                    'down_at' => $log->down_at,
                    'up_at' => $log->up_at,
                    'duration' => $log->duration_human,
                    'reason' => $log->reason,
                    'action' => $log->action,
                    'status' => $log->status,
                ],
                icon: 'bolt',
                color: $isResolved ? 'amber' : 'red',
            );
        }

        // Urutkan kronologis naik (lama → baru).
        usort($events, function ($a, $b) {
            return $a['date']->timestamp <=> $b['date']->timestamp;
        });

        return $events;
    }

    /**
     * Siapkan daftar metadata dokumen untuk satu OrderStatusHistory agar
     * view Tracking dapat merender multi-dokumen (maks 5 × 5 MB per tahap).
     *
     * @return list<array<string, mixed>>
     */
    private function documentsMeta(Order $order, OrderStatusHistory $history): array
    {
        $items = [];
        foreach ($history->documents as $doc) {
            $items[] = [
                'id' => $doc->id,
                'name' => $doc->document_name,
                'ext' => $doc->documentExtension(),
                'size_mb' => $doc->size_mb,
                'preview_url' => route('orders.documents.preview', [$order, $doc]),
                'download_url' => route('orders.documents.raw', [$order, $doc, 'dl' => 1]),
                'raw_url' => route('orders.documents.raw', [$order, $doc]),
                'is_pdf' => $doc->isPdf(),
                'is_image' => $doc->isImage(),
            ];
        }

        return $items;
    }

    /**
     * Factory satu entri event dengan struktur seragam.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function event(
        string $type,
        string $subtype,
        \DateTimeInterface|string $date,
        string $title,
        string $description,
        array $meta,
        string $icon,
        string $color,
    ): array {
        return [
            'type' => $type,
            'subtype' => $subtype,
            'date' => $date,
            'title' => $title,
            'description' => $description,
            'meta' => $meta,
            'icon' => $icon,
            'color' => $color,
        ];
    }

    /**
     * Pilih ikon SVG untuk event perubahan status order.
     */
    private function statusIcon(string $status): string
    {
        return match ($status) {
            OrderStatusService::FINAL_STATUS => 'check-circle',
            OrderStatusService::DISMANTLED_STATUS => 'x-circle',
            default => 'arrow-right',
        };
    }

    /**
     * Pilih warna spotlight (kelas Tailwind color) untuk event status.
     */
    private function statusColor(string $status): string
    {
        return match ($status) {
            'Inquiry' => 'slate',
            'Cek_Ketersediaan' => 'slate',
            'Penawaran' => 'blue',
            'PO_Provider' => 'blue',
            'PO_Vendor' => 'indigo',
            'Instalasi' => 'amber',
            'BAA_BAST' => 'amber',
            'BAST_Vendor' => 'amber',
            OrderStatusService::FINAL_STATUS => 'green',
            OrderStatusService::DISMANTLED_STATUS => 'red',
            default => 'gray',
        };
    }
}