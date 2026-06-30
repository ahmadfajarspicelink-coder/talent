<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DowntimeLog;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controller untuk Modul Ticket sub-modul Logdown.
 *
 * Mengelola catatan downtime client aktif: tambah, ubah, tandai pulih,
 * dan hapus. Daftar utama memisahkan insiden yang masih aktif di bagian
 * atas (perhatian) dan yang sudah pulih di bawahnya (riwayat). Durasi
 * downtime dihitung otomatis dari selisih up_at - down_at.
 */
class LogdownController extends Controller
{
    /**
     * Tampilkan halaman Logdown: form input + daftar insiden.
     */
    public function index(Request $request): View
    {
        $ongoing = DowntimeLog::with(['vendor', 'client', 'creator'])
            ->ongoing()
            ->orderByDesc('down_at')
            ->get();

        $resolved = DowntimeLog::with(['vendor', 'client', 'creator'])
            ->resolved()
            ->orderByDesc('up_at')
            ->limit(100)
            ->get();

        return view('tickets.logdown.index', [
            'ongoing' => $ongoing,
            'resolved' => $resolved,
            'vendors' => Partner::where('type', 'vendor')->orderBy('name')->get(),
            'clients' => Client::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * Simpan catatan downtime baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateLog($request);

        if (($error = $this->guardUpBeforeDown($data)) !== null) {
            return $error;
        }

        $payload = $this->buildPayload($data);

        DowntimeLog::create([...$payload, 'created_by' => Auth::id()]);

        return redirect()
            ->route('logdown.index')
            ->with('status', 'Catatan downtime berhasil ditambahkan.');
    }

    /**
     * Perbarui catatan downtime (termasuk mengubah up_at via form edit).
     */
    public function update(Request $request, DowntimeLog $logdown): RedirectResponse
    {
        $data = $this->validateLog($request, $logdown);

        if (($error = $this->guardUpBeforeDown($data)) !== null) {
            return $error;
        }

        $logdown->fill($this->buildPayload($data))->save();

        return redirect()
            ->route('logdown.index')
            ->with('status', 'Catatan downtime berhasil diperbarui.');
    }

    /**
     * Tandai insiden sebagai pulih: set up_at = now(), hitung durasi.
     */
    public function resolve(DowntimeLog $logdown): RedirectResponse
    {
        if ($logdown->is_resolved) {
            return back()->with('error', 'Insiden ini sudah ditandai pulih.');
        }

        $upAt = now();

        $logdown->update([
            'up_at' => $upAt,
            'duration_seconds' => (int) abs($upAt->getTimestamp() - $logdown->down_at->getTimestamp()),
            'status' => 'up',
        ]);

        return back()->with('status', 'Insiden ditandai pulih. Durasi: ' . $logdown->fresh()->duration_human . '.');
    }

    /**
     * Hapus catatan downtime (admin only — dijaga route middleware).
     */
    public function destroy(DowntimeLog $logdown): RedirectResponse
    {
        $logdown->delete();

        return redirect()
            ->route('logdown.index')
            ->with('status', 'Catatan downtime berhasil dihapus.');
    }

    /**
     * Aturan validasi input form create/update.
     *
     * @return array<string, mixed>
     */
    private function validateLog(Request $request, ?DowntimeLog $existing = null): array
    {
        return $request->validate([
            'vendor_id' => ['nullable', 'integer', 'exists:partners,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'down_at' => ['required', 'date'],
            'up_at' => ['nullable', 'date'],
            'reason' => ['required', 'string'],
            'action' => ['nullable', 'string'],
        ], [
            'down_at.required' => 'Waktu mulai down wajib diisi.',
            'down_at.date' => 'Format waktu mulai down tidak valid.',
            'up_at.date' => 'Format waktu pulih tidak valid.',
            'vendor_id.exists' => 'Vendor yang dipilih tidak valid.',
            'client_id.exists' => 'Client yang dipilih tidak valid.',
            'reason.required' => 'Alasan downtime wajib diisi.',
        ]);
    }

    /**
     * Parse down_at/up_at, hitung durasi & status → payload siap create/update.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(array $data): array
    {
        $downAt = Carbon::parse($data['down_at']);
        $upAt = filled($data['up_at'] ?? null) ? Carbon::parse($data['up_at']) : null;

        return [
            'vendor_id'        => $data['vendor_id'] ?? null,
            'client_id'        => $data['client_id'] ?? null,
            'client_name'      => $this->resolveClientName($data),
            'down_at'          => $downAt,
            'up_at'            => $upAt,
            'duration_seconds' => $upAt !== null ? (int) abs($upAt->getTimestamp() - $downAt->getTimestamp()) : null,
            'status'           => $upAt !== null ? 'up' : 'down',
            'reason'           => $data['reason'],
            'action'           => $data['action'] ?? null,
        ];
    }

    /**
     * Guard: up_at tidak boleh sebelum down_at. Returns redirect error or null.
     */
    private function guardUpBeforeDown(array $data): ?RedirectResponse
    {
        if (blank($data['up_at'] ?? null)) {
            return null;
        }

        $downAt = Carbon::parse($data['down_at']);
        $upAt = Carbon::parse($data['up_at']);

        return $upAt->lt($downAt)
            ? back()->withInput()->with('error', 'Waktu pulih tidak boleh lebih awal dari waktu mulai down.')
            : null;
    }

    /**
     * Tentukan client_name: pakai nama dari relasi bila client_id terisi,
     * fallback ke input manual client_name.
     */
    private function resolveClientName(array $data): ?string
    {
        if (! empty($data['client_id'])) {
            return Client::whereKey($data['client_id'])->value('name')
                ?? ($data['client_name'] ?? null);
        }

        return filled($data['client_name'] ?? null) ? trim($data['client_name']) : null;
    }
}