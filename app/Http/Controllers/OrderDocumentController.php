<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderStatusHistory;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
 * - store(): unggah multi-dokumen (maks 5 per tahap, masing-masing ≤ 5 MB).
 * - destroy(): hapus satu OrderDocument dari sebuah entri riwayat.
 *
 * Keamanan: entri riwayat / dokumen harus benar-benar milik Order pada
 * segmen URL (lihat ensureBelongsToOrder() & ensureDocumentBelongsToOrder()).
 */
class OrderDocumentController extends Controller
{
    /** Batas maksimum jumlah dokumen per OrderStatusHistory. */
    public const MAX_DOCUMENTS_PER_STAGE = 5;

    /** Batas maksimum ukuran per dokumen (kilobytes). 5 MB = 5120 KB. */
    public const MAX_DOCUMENT_SIZE_KB = 5120;

    /** Ekstensi yang diterima untuk dokumen Order. */
    private const ALLOWED_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png';

    /** MIME type yang diterima (H-06: validasi berbasis konten). */
    private const ALLOWED_MIME_TYPES = 'application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png';

    /**
     * Tampilkan halaman pratinjau dokumen.
     */
    public function preview(Order $order, OrderDocument $document): View
    {
        $this->ensureDocumentBelongsToOrder($order, $document);

        return view('orders.document', [
            'order' => $order,
            'document' => $document,
            'history' => $document->history, // backward compat untuk view
        ]);
    }

    /**
     * Alirkan berkas dokumen mentah (inline secara default, atau unduh bila ?dl=1).
     */
    public function raw(Request $request, Order $order, OrderDocument $document): BinaryFileResponse
    {
        $this->ensureDocumentBelongsToOrder($order, $document);

        abort_unless($document->document_path, Response::HTTP_NOT_FOUND);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($document->document_path), Response::HTTP_NOT_FOUND);

        $absolutePath = $disk->path($document->document_path);
        $downloadName = $document->document_name ?: basename($document->document_path);
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
            $contentType = $this->contentTypeFor($document->documentExtension())
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
     * Unggah multi-dokumen untuk sebuah tahap (status) Order.
     *
     * Hanya tahap yang sudah tercapai (indeks <= status saat ini) yang boleh
     * diberi dokumen. Dokumen ditautkan ke entri riwayat status tahap tsb.
     * Jika tahap sudah tercapai namun belum punya entri riwayat (kasus langka),
     * entri dibuat.
     *
     * Batas:
     *   - Maks 5 dokumen per tahap (existing + baru).
     *   - Maks 5 MB per dokumen.
     *   - Hanya ekstensi yang diizinkan (pdf/doc/docx/xls/xlsx/jpg/jpeg/png).
     *   - Validasi mime-type berbasis konten (H-06 anti-executable).
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $statusService = app(OrderStatusService::class);

        $maxFiles = self::MAX_DOCUMENTS_PER_STAGE;

        $validated = $request->validate([
            'status' => ['required', Rule::in(OrderStatusService::STATUSES)],
            'documents' => ['nullable', 'array', 'max:'.$maxFiles],
            'documents.*' => [
                'file',
                'mimes:'.self::ALLOWED_EXTENSIONS,
                'mimetypes:'.self::ALLOWED_MIME_TYPES,
                'max:'.self::MAX_DOCUMENT_SIZE_KB,
            ],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'documents.max' => 'Maksimal '.$maxFiles.' dokumen per tahap.',
            'documents.*.mimes' => 'Dokumen harus berformat pdf, doc, docx, xls, xlsx, jpg, atau png.',
            'documents.*.mimetypes' => 'Konten dokumen tidak sesuai ekstensi file (kemungkinan file berisi executable).',
            'documents.*.max' => 'Ukuran setiap dokumen maksimal 5 MB.',
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

        $files = $request->file('documents', []);
        $existingCount = $history->documents()->count();
        $newCount = is_array($files) ? count($files) : 0;
        $totalAfter = $existingCount + $newCount;

        if ($totalAfter > $maxFiles) {
            $remaining = max(0, $maxFiles - $existingCount);

            return redirect()->back()->with(
                'error',
                "Batas maksimum {$maxFiles} dokumen per tahap terlampaui. Tersisa {$remaining} slot untuk tahap ini."
            );
        }

        $hasNewFiles = $newCount > 0;
        $noteProvided = $request->has('note');

        // Harus ada yang dikerjakan: unggah berkas, isi catatan, atau sudah
        // ada dokumen sebelumnya (saat hanya memperbarui catatan).
        if (! $hasNewFiles && ! $noteProvided && $existingCount === 0) {
            return redirect()->back()->with('error', 'Pilih berkas dokumen terlebih dahulu.');
        }

        DB::transaction(function () use ($history, $files, $order) {
            $uploadedBy = Auth::id();
            $directory = "order-documents/{$order->id}";

            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $path = $file->store($directory, 'public');

                OrderDocument::create([
                    'order_status_history_id' => $history->id,
                    'document_path' => $path,
                    'document_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize() ?: 0,
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => $uploadedBy,
                ]);
            }

            // Simpan/perbarui catatan (mis. nomor PO provider) bila field dikirim.
            if (request()->has('note')) {
                $note = trim((string) request()->input('note', ''));
                $history->note = ($note === '') ? null : $note;
                $history->save();
            }
        });

        return redirect()->back()->with('status', 'Dokumen berhasil disimpan.');
    }

    /**
     * Hapus satu OrderDocument (tanda × di UI). Entri OrderStatusHistory
     * tetap dipertahankan sebagai catatan perubahan status; hanya satu
     * berkas OrderDocument yang dihapus.
     */
    public function destroy(Order $order, OrderDocument $document): RedirectResponse
    {
        $this->ensureDocumentBelongsToOrder($order, $document);

        if ($document->document_path) {
            Storage::disk('public')->delete($document->document_path);
        }

        $document->delete();

        return redirect()->back()->with('status', 'Dokumen berhasil dihapus.');
    }

    /**
     * Pastikan entri riwayat memang milik Order yang diminta.
     */
    private function ensureBelongsToOrder(Order $order, OrderStatusHistory $history): void
    {
        abort_unless((int) $history->order_id === (int) $order->id, Response::HTTP_NOT_FOUND);
    }

    /**
     * Pastikan OrderDocument (lewat history-nya) milik Order yang diminta.
     */
    private function ensureDocumentBelongsToOrder(Order $order, OrderDocument $document): void
    {
        $history = $document->history;
        abort_unless($history !== null, Response::HTTP_NOT_FOUND);
        $this->ensureBelongsToOrder($order, $history);
    }
}