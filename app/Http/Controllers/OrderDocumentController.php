<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyajikan dokumen yang dilampirkan pada riwayat status sebuah Order.
 *
 * - preview(): halaman dalam aplikasi yang menampilkan isi dokumen
 *   (PDF di viewer, gambar sebagai <img>, format lain → tombol unduh).
 * - raw(): mengalirkan berkas mentah dengan disposisi inline agar bisa
 *   diembed (iframe/img) atau dibuka langsung; mendukung paksa unduh (?dl=1).
 *
 * Keamanan: entri riwayat harus benar-benar milik Order pada segmen URL.
 */
class OrderDocumentController extends Controller
{
    /**
     * Tampilkan halaman pratinjau dokumen sebuah entri riwayat.
     */
    public function preview(Order $order, OrderStatusHistory $history): View
    {
        $this->ensureBelongsToOrder($order, $history);

        return view('orders.document', [
            'order' => $order,
            'history' => $history,
        ]);
    }

    /**
     * Alirkan berkas dokumen mentah (inline secara default, atau unduh bila ?dl=1).
     */
    public function raw(Request $request, Order $order, OrderStatusHistory $history): BinaryFileResponse
    {
        $this->ensureBelongsToOrder($order, $history);

        abort_unless($history->hasDocument(), Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($history->document_path), Response::HTTP_NOT_FOUND);

        $absolutePath = $disk->path($history->document_path);
        $downloadName = $history->document_name ?: basename($history->document_path);
        $isDownload = $request->boolean('dl');

        $response = response()->file($absolutePath, [
            'Content-Disposition' => sprintf(
                '%s; filename="%s"',
                $isDownload ? 'attachment' : 'inline',
                addslashes($downloadName)
            ),
        ]);

        // Tetapkan Content-Type yang benar agar browser merender inline
        // (mis. PDF/gambar). Tanpa ini, deteksi MIME bisa keliru menjadi
        // application/octet-stream sehingga berkas justru terunduh.
        if (! $isDownload) {
            $contentType = $this->contentTypeFor($history->documentExtension())
                ?? mime_content_type($absolutePath)
                ?: 'application/octet-stream';

            $response->headers->set('Content-Type', $contentType);
            // Izinkan ditampilkan di iframe pada origin yang sama.
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        return $response;
    }

    /**
     * Pemetaan ekstensi → MIME type untuk penyajian inline yang andal.
     */
    private function contentTypeFor(?string $extension): ?string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            default => null,
        };
    }

    /**
     * Unggah/ganti dokumen untuk sebuah tahap (status) Order.
     *
     * Hanya tahap yang sudah tercapai (indeks <= status saat ini) yang boleh
     * diberi dokumen. Dokumen ditautkan ke entri riwayat status tahap tsb.
     * Jika tahap sudah tercapai namun belum punya entri riwayat (kasus langka),
     * entri dibuat. Dokumen lama (bila ada) diganti.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $statusService = app(OrderStatusService::class);

        $validated = $request->validate([
            'status' => ['required', Rule::in(OrderStatusService::STATUSES)],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png',
                'max:10240',
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'document.mimes' => 'Dokumen harus berformat pdf, doc, docx, xls, xlsx, jpg, atau png.',
            'document.mimetypes' => 'Konten dokumen tidak sesuai ekstensi file (kemungkinan file berisi executable).',
            'document.max' => 'Ukuran dokumen maksimal 10 MB.',
        ]);

        $status = $validated['status'];

        // Tahap harus sudah tercapai untuk bisa dilampiri dokumen.
        if ($statusService->indexOf($status) > $statusService->indexOf($order->status)) {
            return redirect()->back()->with('error', 'Tahap belum tercapai, dokumen belum bisa diunggah.');
        }

        $history = $order->statusHistories()->where('status', $status)->latest('id')->first();
        if ($history === null) {
            $history = $order->statusHistories()->create([
                'status' => $status,
                'changed_at' => now(),
            ]);
        }

        $hasNewFile = $request->hasFile('document');
        // Form menyertakan field 'note' (mis. referensi PO) untuk tahap tertentu.
        $noteProvided = $request->has('note');

        // Harus ada yang dikerjakan: unggah berkas, isi catatan, atau berkas
        // sudah ada sebelumnya (saat hanya memperbarui catatan).
        if (! $hasNewFile && ! $noteProvided && ! $history->document_path) {
            return redirect()->back()->with('error', 'Pilih berkas dokumen terlebih dahulu.');
        }

        // Ganti dokumen lama bila mengunggah berkas baru.
        if ($hasNewFile) {
            if ($history->document_path) {
                Storage::disk('public')->delete($history->document_path);
            }

            $file = $request->file('document');
            $history->document_path = $file->store("order-documents/{$order->id}", 'public');
            $history->document_name = $file->getClientOriginalName();
        }

        // Simpan/perbarui catatan (mis. nomor PO provider) bila field dikirim.
        if ($noteProvided) {
            $note = trim((string) ($validated['note'] ?? ''));
            $history->note = ($note === '') ? null : $note;
        }

        $history->save();

        return redirect()->back()->with('status', 'Dokumen berhasil disimpan.');
    }

    /**
     * Hapus dokumen pada sebuah entri riwayat (tanda × di UI). Entri riwayat
     * tetap dipertahankan sebagai catatan perubahan status; hanya berkasnya
     * yang dihapus.
     */
    public function destroy(Order $order, OrderStatusHistory $history): RedirectResponse
    {
        $this->ensureBelongsToOrder($order, $history);

        if ($history->document_path) {
            Storage::disk('public')->delete($history->document_path);
        }

        $history->update([
            'document_path' => null,
            'document_name' => null,
        ]);

        return redirect()->back()->with('status', 'Dokumen berhasil dihapus.');
    }

    /**
     * Pastikan entri riwayat memang milik Order yang diminta.
     */
    private function ensureBelongsToOrder(Order $order, OrderStatusHistory $history): void
    {
        abort_unless((int) $history->order_id === (int) $order->id, Response::HTTP_NOT_FOUND);
    }
}
