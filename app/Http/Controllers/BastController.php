<?php

namespace App\Http\Controllers;

use App\Models\BastDocument;
use App\Models\Order;
use App\Services\BastGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BastController extends Controller
{
    public function __construct(
        private readonly BastGeneratorService $bastService,
    ) {}

    /**
     * Generate (atau regenerate) dokumen BAA & BAST dari template.
     *
     * Menerima parameter opsional `baa_number` dan `bast_number` agar tombol
     * "Generate dari Template" pada form input tahap BAA_BAST bisa langsung
     * menyertakan nilai yang baru diketik tanpa mewajibkan klik "Tandai
     * Selesai" terlebih dahulu.
     */
    public function generate(Request $request, Order $order): RedirectResponse
    {
        $this->syncDocumentNumbers($request, $order);
        $this->deleteOldDocuments($order);

        $results = DB::transaction(fn () => array_map(
            fn (array $doc) => BastDocument::create([
                'order_id'      => $order->id,
                'type'          => $doc['type'],
                'document_path' => $doc['path'],
                'generated_at'  => now(),
            ]),
            $this->bastService->generate($order),
        ));

        return redirect()
            ->back()
            ->with('status', 'Dokumen BAA & BAST berhasil di-generate.');
    }

    public function download(Order $order, BastDocument $bastDocument): BinaryFileResponse
    {
        abort_unless($bastDocument->order_id === $order->id, 404);

        $fullPath = storage_path("app/public/{$bastDocument->document_path}");
        abort_unless(File::exists($fullPath), 404, 'File tidak ditemukan.');

        return response()->download($fullPath, basename($bastDocument->document_path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function downloadAll(Order $order): StreamedResponse|RedirectResponse
    {
        $docs = $order->bastDocuments;

        if ($docs->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Belum ada dokumen BAST yang di-generate.');
        }

        $zipPath = storage_path("app/public/bast-documents/{$order->id}/BAA_BAST_{$order->id}.zip");
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file ZIP.');
        }

        foreach ($docs as $doc) {
            $fullPath = storage_path("app/public/{$doc->document_path}");
            if (File::exists($fullPath)) {
                $zip->addFile($fullPath, basename($doc->document_path));
            }
        }

        $zip->close();

        $filename = "BAA_BAST_{$order->display_number}.zip";

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Simpan nomor BAA/BAST yang dikirim (bila ada) agar templat
     * yang dihasilkan memuat nilai baru.
     */
    private function syncDocumentNumbers(Request $request, Order $order): void
    {
        $updates = array_filter([
            'baa_number'  => $request->input('baa_number'),
            'bast_number' => $request->input('bast_number'),
        ], filled(...));

        if ($updates !== []) {
            $order->fill(array_map(trim(...), $updates))->save();
        }
    }

    /**
     * Hapus dokumen BAST lama (file + DB) sebelum generate ulang.
     */
    private function deleteOldDocuments(Order $order): void
    {
        BastDocument::where('order_id', $order->id)
            ->get()
            ->each(function (BastDocument $doc) {
                Storage::disk('public')->delete($doc->document_path);
                $doc->delete();
            });
    }
}