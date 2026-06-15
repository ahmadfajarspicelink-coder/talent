<?php

namespace App\Http\Controllers;

use App\Models\BastDocument;
use App\Models\Order;
use App\Services\BastGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BastController extends Controller
{
    public function generate(Order $order): RedirectResponse
    {
        $service = new BastGeneratorService();

        // Hapus dokumen lama
        $oldDocs = BastDocument::where('order_id', $order->id)->get();
        foreach ($oldDocs as $doc) {
            if (Storage::disk('public')->exists($doc->document_path)) {
                Storage::disk('public')->delete($doc->document_path);
            }
            $doc->delete();
        }

        $results = $service->generate($order);

        foreach ($results as $doc) {
            BastDocument::create([
                'order_id'      => $order->id,
                'type'          => $doc['type'],
                'document_path' => $doc['path'],
                'generated_at'  => now(),
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Dokumen BAA & BAST berhasil di-generate.');
    }

    public function download(Order $order, BastDocument $bastDocument): BinaryFileResponse
    {
        abort_unless($bastDocument->order_id === $order->id, 404);

        $fullPath = storage_path("app/public/{$bastDocument->document_path}");
        abort_unless(File::exists($fullPath), 404, 'File tidak ditemukan.');

        $filename = basename($bastDocument->document_path);

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function downloadAll(Order $order): StreamedResponse|RedirectResponse
    {
        $docs = BastDocument::where('order_id', $order->id)->get();

        if ($docs->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Belum ada dokumen BAST yang di-generate.');
        }

        $zipPath = storage_path("app/public/bast-documents/{$order->id}/BAA_BAST_{$order->id}.zip");
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()
                ->back()
                ->with('error', 'Gagal membuat file ZIP.');
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
}