<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\Partner;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controller untuk Modul_Order (Requirement 5 & 6).
 *
 * Controller sengaja dibuat tipis: hanya orkestrasi antara Form Request,
 * Eloquent Model, dan OrderStatusService (lihat design.md "Components and
 * Interfaces" → Modul Order). Logika domain (transisi status, progres)
 * berada di OrderStatusService agar dapat diuji terisolasi.
 *
 * Struktur kelas (urutan method mengikuti alur lifecycle Order):
 *   - create()  : tampilkan form pembuatan Order            (task 11.2, R5.1/R5.3)
 *   - store()   : simpan Order baru berstatus Inquiry        (task 11.2, R5.1/R5.3/R5.4)
 *   - index()   : daftar Order                               (task 12.1, R5.5/R6.8) — DITAMBAHKAN NANTI
 *   - show()    : detail Order + riwayat status              (task 12.1, R6.5/R6.9) — DITAMBAHKAN NANTI
 *   - advanceStatus() : majukan Status_Order                 (task 12.2, R6.2..R6.6) — DITAMBAHKAN NANTI
 *
 * CATATAN UNTUK TASK 12.1 / 12.2: tambahkan method index()/show()/advanceStatus()
 * pada section bertanda di bawah ("BEGIN tracking methods") tanpa mengubah
 * create()/store(). OrderStatusService dapat di-inject via constructor atau
 * di-resolve langsung di method baru sesuai kebutuhan.
 */
class OrderController extends Controller
{
    // ===================================================================
    // BEGIN order creation methods (task 11.2) — R5.1, R5.3, R5.4
    // ===================================================================

    /**
     * Tampilkan form pembuatan Order baru.
     *
     * Menyediakan daftar Client, Provider (partners type=provider), dan
     * Vendor (partners type=vendor) untuk dipilih di satu form. Form ini
     * juga menampung empat komponen harga (R5.3) — provider/vendor OTC & MRC.
     */
    public function create(): View
    {
        return view('orders.create', [
            'providers' => Partner::where('type', 'provider')->orderBy('name')->get(),
            'vendors' => Partner::where('type', 'vendor')->orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan Order baru.
     *
     * Client tidak dipilih dari daftar, melainkan diisi sebagai nama (R5.2).
     * Sistem mencari Client dengan nama tersebut atau membuatnya baru dengan
     * status awal 'inactive'. Client baru ini BELUM ditampilkan di Modul_Client
     * sampai salah satu Order miliknya mencapai Complete (lihat OrderObserver,
     * R4.5) — Modul_Client hanya menampilkan client berstatus aktif.
     *
     * Status awal Order selalu Inquiry (R5.1, Property 5). Empat komponen harga
     * dicatat dalam satu Order (R5.3). Validasi field wajib (R5.2) dan harga
     * non-negatif (R5.4) ditangani di StoreOrderRequest. Penyimpanan Order,
     * Client, dan riwayatnya dibungkus transaksi agar atomik.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $initialStatus = OrderStatusService::STATUSES[0]; // 'Inquiry'

        $data = $request->validated();
        $clientName = trim($data['client_name']);
        $clientAddress = isset($data['client_address']) ? trim((string) $data['client_address']) : null;
        $note = isset($data['note']) ? trim((string) $data['note']) : null;

        $order = DB::transaction(function () use ($data, $clientName, $clientAddress, $note, $initialStatus) {
            // Cari Client berdasarkan nama, atau buat baru (inactive) — relasi
            // dari Order, bukan input manual di Modul_Client.
            $client = Client::firstOrCreate(
                ['name' => $clientName],
                ['status' => 'inactive'],
            );

            // Simpan/perbarui alamat client dari input order.
            if ($clientAddress !== null && $clientAddress !== '') {
                $client->update(['address' => $clientAddress]);
            }

            $order = Order::create([
                'client_id' => $client->id,
                'provider_id' => $data['provider_id'],
                'vendor_id' => $data['vendor_id'] ?? null,
                'note' => ($note === '') ? null : $note,
                'status' => $initialStatus,
            ]);

            // Nomor order otomatis: ORD-{YYYYMMDD}-{6 hex} (mis. ORD-20260609-4b41e7).
            $order->update([
                'order_number' => 'ORD-'.now()->format('Ymd').'-'.bin2hex(random_bytes(3)),
            ]);

            $order->statusHistories()->create([
                'status' => $initialStatus,
                'changed_at' => now(),
            ]);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Order berhasil dibuat.');
    }

    // ===================================================================
    // END order creation methods (task 11.2)
    // ===================================================================

    // ===================================================================
    // BEGIN tracking methods (tasks 12.1 / 12.2)
    // Tambahkan index(), show(), dan advanceStatus() di sini.
    // ===================================================================

    /**
     * Tampilkan daftar seluruh Order (R5.5, R6.8, R6.10).
     *
     * Setiap Order ditampilkan beserta Client, Provider, Vendor, dan
     * Status_Order saat ini (R5.5). Relasi di-eager-load untuk menghindari
     * query N+1 saat Blade merender setiap baris. OrderStatusService ikut
     * diteruskan ke view agar Blade dapat menghitung Persentase_Progress dan
     * merender Indikator_Progress per Order (R6.8); karena progres dihitung
     * dari Status_Order saat ini, tampilan otomatis selaras dengan status
     * terbaru setiap Order (R6.10).
     */
    public function index(): View
    {
        // Sembunyikan Order yang sudah digantikan oleh Order upgrade (punya turunan).
        $orders = Order::with(['client', 'provider', 'vendor'])
            ->whereDoesntHave('upgrades')
            ->orderBy('id')
            ->get();

        return view('orders.index', [
            'orders' => $orders,
            'statusService' => app(OrderStatusService::class),
        ]);
    }

    /**
     * Tampilkan detail sebuah Order beserta riwayat statusnya (R6.5, R6.9, R6.10).
     *
     * Relasi Client/Provider/Vendor di-eager-load untuk ditampilkan, dan
     * seluruh riwayat perubahan Status_Order dimuat terurut berdasarkan
     * waktu perubahan (changed_at menaik) sehingga riwayat tampil kronologis
     * lengkap dengan waktunya (R6.5). OrderStatusService diteruskan agar Blade
     * dapat merender Indikator_Progress sesuai Status_Order saat ini (R6.9);
     * progres dihitung dari status terbaru sehingga tampilan selalu mengikuti
     * perubahan status (R6.10).
     */
    public function show(Order $order): View
    {
        $order->load([
            'client',
            'provider',
            'vendor',
            'package',
            'parentOrder',
            'bastDocuments',
            'statusHistories' => fn ($query) => $query->orderBy('changed_at'),
        ]);

        return view('orders.show', [
            'order' => $order,
            'statusHistories' => $order->statusHistories,
            'documentsByStatus' => $order->statusHistories
                ->filter(fn ($h) => $h->hasDocument())
                ->keyBy('status'),
            'statusService' => app(OrderStatusService::class),
            'packages' => \App\Models\Package::orderBy('name')->get(),
        ]);
    }

    /**
     * Majukan Status_Order sebuah Order ke tahap berikutnya (R6.2..R6.6).
     *
     * Target ditentukan dari input request ('status' atau 'target'); bila
     * tidak disertakan, dihitung otomatis sebagai penerus langsung via
     * OrderStatusService::nextStatus(). Aturan domain ditegakkan lewat
     * OrderStatusService:
     *   - Order yang sudah Complete tidak boleh diubah lagi (R6.6).
     *   - Transisi hanya sah ke tahap berurutan berikutnya; melompat/mundur
     *     ditolak (R6.3).
     * Saat transisi sah, Status_Order diperbarui DAN satu entri riwayat
     * (status baru + waktu perubahan) dicatat dalam satu transaksi agar
     * atomik (R6.2, R6.4).
     */
    public function advanceStatus(Request $request, Order $order): RedirectResponse
    {
        $statusService = app(OrderStatusService::class);

        $current = $order->status;

        // Order yang sudah selesai (Client_Aktif) tidak boleh diubah lagi.
        if ($statusService->isComplete($current)) {
            return redirect()
                ->back()
                ->with('error', 'Order sudah selesai');
        }

        // Target dari input ('status'/'target') atau hitung penerus langsung.
        $target = $request->input('status', $request->input('target'))
            ?? $statusService->nextStatus($current);

        // Transisi harus mengikuti urutan: hanya penerus langsung yang sah.
        if ($target === null || ! $statusService->canTransition($current, $target)) {
            return redirect()
                ->back()
                ->with('error', 'status harus mengikuti urutan');
        }

        // Validasi: dokumen opsional + field wajib khusus tahap tujuan.
        $rules = array_merge([
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:10240',
            ],
        ], $this->stageFieldRules($target));

        $validated = $request->validate($rules, [
            'document.mimes' => 'Dokumen harus berformat pdf, doc, docx, xls, xlsx, jpg, atau png.',
            'document.max' => 'Ukuran dokumen maksimal 10 MB.',
            'offer_number.required' => 'Nomor penawaran wajib diisi.',
            'po_provider_number.required' => 'Nomor PO provider wajib diisi.',
            'po_vendor_number.required' => 'Nomor PO vendor wajib diisi.',
            'provider_otc.required' => 'OTC provider wajib diisi.',
            'provider_mrc.required' => 'MRC provider wajib diisi.',
            'vendor_otc.required' => 'OTC vendor wajib diisi.',
            'vendor_mrc.required' => 'MRC vendor wajib diisi.',
            'bandwidth.required' => 'Bandwidth wajib diisi.',
            'baa_number.required' => 'Nomor BAA wajib diisi.',
            'bast_number.required' => 'Nomor BAST wajib diisi.',
            'contract_months.required' => 'Durasi kontrak wajib diisi.',
            'contract_months.min' => 'Durasi kontrak minimal 12 bulan.',
        ]);

        // Field tahap yang akan disimpan ke Order (key = nama kolom).
        $orderUpdates = array_intersect_key($validated, array_flip([
            'offer_number',
            'package_id',
            'po_provider_number',
            'po_vendor_number',
            'provider_otc',
            'provider_mrc',
            'vendor_otc',
            'vendor_mrc',
            'bandwidth',
            'baa_number',
            'bast_number',
            'contract_months',
        ]));

        // Simpan file ke disk publik (jika ada) sebelum transaksi DB.
        $documentPath = null;
        $documentName = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $documentName = $file->getClientOriginalName();
            $documentPath = $file->store("order-documents/{$order->id}", 'public');
        }

        DB::transaction(function () use ($order, $target, $orderUpdates, $documentPath, $documentName, $statusService) {
            $order->fill($orderUpdates);

            // Saat mencapai tahap akhir: tetapkan masa kontrak client.
            if ($target === OrderStatusService::FINAL_STATUS) {
                $months = (int) ($order->contract_months ?? 0);
                $order->contract_start_date = now()->toDateString();
                $order->contract_end_date = now()->addMonths($months)->toDateString();
            }

            $order->status = $target;
            $order->save();

            $order->statusHistories()->create([
                'status' => $target,
                'document_path' => $documentPath,
                'document_name' => $documentName,
                'changed_at' => now(),
            ]);
        });

        return redirect()
            ->back()
            ->with('status', "Status Order berhasil dimajukan ke {$statusService->title($target)}.");
    }

    /**
     * Aturan validasi field wajib untuk tiap tahap tujuan. Tahap tanpa field
     * (Cek_Ketersediaan, Instalasi, BAST_Vendor) mengembalikan array kosong
     * sehingga hanya butuh konfirmasi "Tandai Selesai".
     *
     * @return array<string, array<int, string>>
     */
    private function stageFieldRules(string $target): array
    {
        return match ($target) {
            'Penawaran' => [
                'offer_number' => ['required', 'string', 'max:255'],
            ],
            'PO_Provider' => [
                'package_id' => ['required', 'exists:packages,id'],
                'po_provider_number' => ['required', 'string', 'max:255'],
                'provider_otc' => ['required', 'integer', 'min:0'],
                'provider_mrc' => ['required', 'integer', 'min:0'],
                'bandwidth' => ['required', 'integer', 'min:0'],
            ],
            'PO_Vendor' => [
                'po_vendor_number' => ['required', 'string', 'max:255'],
                'vendor_otc' => ['required', 'integer', 'min:0'],
                'vendor_mrc' => ['required', 'integer', 'min:0'],
            ],
            'BAA_BAST' => [
                'baa_number' => ['required', 'string', 'max:255'],
                'bast_number' => ['required', 'string', 'max:255'],
            ],
            'Client_Aktif' => [
                'contract_months' => ['required', 'integer', 'min:12'],
            ],
            default => [],
        };
    }

    /**
     * Hapus Order beserta riwayat status dan berkas dokumennya.
     *
     * Hanya Admin yang boleh menghapus Order; Staff ditolak (403). Berkas
     * dokumen pada tiap entri riwayat ikut dihapus dari storage agar tidak
     * meninggalkan file yatim. Seluruh proses dibungkus transaksi.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        DB::transaction(function () use ($order) {
            foreach ($order->statusHistories as $history) {
                if ($history->document_path) {
                    Storage::disk('public')->delete($history->document_path);
                }
            }

            $order->statusHistories()->delete();
            $order->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('status', "Order {$order->display_number} berhasil dihapus.");
    }
}
