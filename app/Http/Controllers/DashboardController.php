<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DowntimeLog;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Ringkasan operasional untuk halaman Dashboard: kartu statistik +
 * panel Tracking Client.
 *
 * Modul dashboard kini menonjolkan Tracking Client — view-only riwayat
 * lengkap tiap client aktif yang menggabungkan order, logdown, upgrade
 * bandwidth, dan dismantle dalam satu kronologi. Daftar client aktif
 * ditampilkan dalam kartu ringkas di dashboard; admin dapat membuka
 * Tracking Client lengkap tiap client lewat route clients.tracking.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $completeStatus = OrderStatusService::STATUSES[array_key_last(OrderStatusService::STATUSES)];

        // Statistik ringkas.
        $ordersInProgress = Order::where('status', '!=', $completeStatus)->count();
        $ordersCompleted = Order::where('status', $completeStatus)->count();
        $activeClients = Client::where('status', 'active')->count();

        // Total margin (hanya Order dengan harga lengkap).
        $marginOtc = (int) Order::whereNotNull('provider_otc')->whereNotNull('vendor_otc')
            ->sum(DB::raw('provider_otc - vendor_otc'));
        $marginMrc = (int) Order::whereNotNull('provider_mrc')->whereNotNull('vendor_mrc')
            ->sum(DB::raw('provider_mrc - vendor_mrc'));

        // Tracking Client: daftar client aktif + ringkasan aktivitasnya.
        $trackingClients = $this->buildTrackingSummary();

        return view('dashboard', [
            'ordersInProgress' => $ordersInProgress,
            'ordersCompleted' => $ordersCompleted,
            'activeClients' => $activeClients,
            'marginOtc' => $marginOtc,
            'marginMrc' => $marginMrc,
            'trackingClients' => $trackingClients,
            'statusService' => app(OrderStatusService::class),
        ]);
    }

    /**
     * Bangun ringkasan Tracking Client untuk ditampilkan di dashboard.
     *
     * Untuk setiap client aktif, kumpulkan:
     *   • Identitas dasar + layanan aktif terakhir.
     *   • Total order (semua status).
     *   • Total logdown.
     *   • Total upgrade (order.order_type = upgrade).
     *   • Total dismantle (order.status = dismantled).
     *   • Event terbaru (timestamp + judul) untuk kartu spotlight.
     *
     * Data disajikan dalam bentuk koleksi yang siap di-loop di view.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildTrackingSummary(): \Illuminate\Support\Collection
    {
        $clients = Client::where('status', 'active')
            ->with([
                'orders' => fn ($q) => $q->with(['provider', 'vendor', 'package']),
                'orders.statusHistories',
            ])
            ->orderBy('name')
            ->get();

        if ($clients->isEmpty()) {
            return collect();
        }

        $clientIds = $clients->pluck('id');

        // Logdown digrupkan per-client_id untuk lookup O(1).
        $logdownCounts = DowntimeLog::whereIn('client_id', $clientIds)
            ->selectRaw('client_id, COUNT(*) as total, MAX(down_at) as latest_down_at')
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        $logdownLatest = DowntimeLog::whereIn('client_id', $clientIds)
            ->orderByDesc('down_at')
            ->get()
            ->groupBy('client_id')
            ->map(fn ($grp) => $grp->first());

        return $clients->map(function (Client $client) use ($logdownCounts, $logdownLatest) {
            $orders = $client->orders;

            $totalOrders = $orders->count();
            $totalUpgrade = $orders->where('order_type', 'upgrade')->count();
            $totalDismantle = $orders->where('status', OrderStatusService::DISMANTLED_STATUS)->count();
            $totalLogdown = (int) ($logdownCounts[$client->id]->total ?? 0);

            $latestOrder = $orders->sortByDesc('created_at')->first();
            $latestHistory = $orders->flatMap->statusHistories->sortByDesc('changed_at')->first();

            // Bangun dua event spotlight terbaru untuk kartu dashboard.
            $events = collect();

            if ($latestHistory) {
                $events->push([
                    'type' => 'order',
                    'title' => app(OrderStatusService::class)->title($latestHistory->status),
                    'date' => $latestHistory->changed_at,
                ]);
            }

            if ($latestOrder) {
                $events->push([
                    'type' => $latestOrder->order_type === 'upgrade' ? 'upgrade' : 'order',
                    'title' => $latestOrder->order_type === 'upgrade' ? 'Upgrade Bandwidth' : 'Order Dibuat',
                    'date' => $latestOrder->created_at,
                ]);
            }

            $latestDown = $logdownLatest[$client->id] ?? null;
            if ($latestDown) {
                $events->push([
                    'type' => 'logdown',
                    'title' => $latestDown->up_at ? 'Logdown Pulih' : 'Logdown Aktif',
                    'date' => $latestDown->down_at,
                ]);
            }

            $latestDismantled = $orders
                ->where('status', OrderStatusService::DISMANTLED_STATUS)
                ->sortByDesc('dismantled_at')
                ->first();
            if ($latestDismantled && $latestDismantled->dismantled_at) {
                $events->push([
                    'type' => 'dismantle',
                    'title' => 'Dismantle',
                    'date' => $latestDismantled->dismantled_at,
                ]);
            }

            // Spotlight = 2 event paling baru.
            $spotlight = $events->sortByDesc('date')->take(2)->values();

            // Layanan aktif = order Client_Aktif terbaru, fallback ke order terbaru.
            $activeOrder = $orders->where('status', OrderStatusService::FINAL_STATUS)
                ->sortByDesc('updated_at')
                ->first() ?? $orders->sortByDesc('updated_at')->first();

            return [
                'client' => $client,
                'active_order' => $activeOrder,
                'total_orders' => $totalOrders,
                'total_logdown' => $totalLogdown,
                'total_upgrade' => $totalUpgrade,
                'total_dismantle' => $totalDismantle,
                'spotlight' => $spotlight,
                'latest_activity_at' => $events->max('date'),
            ];
        })->sortByDesc('latest_activity_at')->values();
    }
}