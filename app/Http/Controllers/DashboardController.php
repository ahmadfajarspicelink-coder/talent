<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Ringkasan operasional untuk halaman Dashboard: kartu statistik + daftar
 * Order terbaru (merujuk data Modul Order).
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

        // Order terbaru.
        $recentOrders = Order::with('client')->latest()->take(8)->get();

        return view('dashboard', [
            'ordersInProgress' => $ordersInProgress,
            'ordersCompleted' => $ordersCompleted,
            'activeClients' => $activeClients,
            'marginOtc' => $marginOtc,
            'marginMrc' => $marginMrc,
            'recentOrders' => $recentOrders,
            'statusService' => app(OrderStatusService::class),
        ]);
    }
}
