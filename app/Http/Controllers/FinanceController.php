<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Services\MarginService;
use App\Services\OrderStatusService;
use Illuminate\View\View;

/**
 * Laporan margin keuangan (Modul_Finance, Requirement 7).
 *
 * Controller tipis: hanya memuat data dari Eloquent lalu mendelegasikan
 * seluruh perhitungan margin ke MarginService (logika domain murni).
 */
class FinanceController extends Controller
{
    public function __construct(private readonly MarginService $marginService)
    {
    }

    /**
     * Laporan margin per Order untuk client aktif (R7.3, R7.5, R7.6).
     *
     * Menampilkan rekap margin OTC & MRC dari Order milik client yang sudah
     * aktif (Order berstatus Client_Aktif). Setiap baris dilengkapi
     * Total_Revenue_Kontrak = Margin_OTC + (Margin_MRC × lama kontrak bulan).
     * Tiga kartu ringkasan mengagregasi seluruh baris yang harganya lengkap.
     */
    public function orderReport(): View
    {
        $orders = Order::with(['client', 'provider', 'vendor'])
            ->where('status', OrderStatusService::FINAL_STATUS)
            ->whereDoesntHave('upgrades')
            ->whereHas('client', fn ($query) => $query->where('status', 'active'))
            ->get();

        // Lengkapi tiap baris margin dengan lama kontrak & total revenue kontrak.
        $orderMargins = $this->marginService->orderMargins($orders)->map(function (array $row): array {
            $months = (int) ($row['order']->contract_months ?? 0);

            $row['contract_months'] = $months;
            $row['total_revenue'] = $row['available']
                ? $row['otc'] + ($row['mrc'] * $months)
                : null;

            return $row;
        });

        $summary = [
            'otc' => (int) $orderMargins->sum(fn (array $row): int => $row['otc'] ?? 0),
            'mrc' => (int) $orderMargins->sum(fn (array $row): int => $row['mrc'] ?? 0),
            'revenue' => (int) $orderMargins->sum(fn (array $row): int => $row['total_revenue'] ?? 0),
        ];

        return view('finance.orders', [
            'orderMargins' => $orderMargins,
            'summary' => $summary,
        ]);
    }

    /**
     * Laporan Total_Margin_Per_Client (R7.4).
     *
     * Untuk setiap Client, agregasi Margin_OTC & Margin_MRC dari seluruh
     * Order miliknya melalui MarginService::totalMarginPerClient.
     */
    public function clientReport(): View
    {
        $clients = Client::with('orders')->get();

        $totalMarginPerClient = $clients->map(fn (Client $client): array => [
            'client' => $client,
            'total' => $this->marginService->totalMarginPerClient($client),
        ]);

        return view('finance.clients', [
            'Total_Margin_Per_Client' => $totalMarginPerClient,
        ]);
    }
}
