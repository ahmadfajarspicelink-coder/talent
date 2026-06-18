{{--
    Pratinjau dokumen Order (1 OrderDocument = 1 berkas).
    Controller (OrderDocumentController::preview) meneruskan variabel
    $document (OrderDocument) dan $history (OrderStatusHistory pemilik).

    - PDF  : ditampilkan di viewer browser (iframe).
    - Gambar: ditampilkan sebagai <img>.
    - Lainnya (doc/docx/xls/xlsx/dll): tidak bisa dirender browser, tampilkan
      info + tombol unduh.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-200 leading-tight">
                {{ __('Dokumen') }} — {{ $history->status }} ({{ __('Order') }} {{ $order->display_number }})
            </h2>
            <a href="{{ route('orders.show', $order) }}"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 font-medium">{{ __('← Kembali ke Order') }}</a>
        </div>
    </x-slot>

    @php($rawUrl = route('orders.documents.raw', [$order, $document]))
    @php($downloadUrl = route('orders.documents.raw', [$order, $document, 'dl' => 1]))
    @php($docName = $document->document_name ?? __('Dokumen'))
    @php($docExt = $document->documentExtension())

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-700 dark:text-slate-300">
                    <span class="font-medium">{{ $docName }}</span>
                    <span class="ml-2 text-xs uppercase text-gray-400 dark:text-slate-500">{{ $docExt }}</span>
                    @if (! empty($document->size))
                        <span class="ml-2 text-xs text-gray-400 dark:text-slate-500">{{ $document->size_mb }} MB</span>
                    @endif
                    <span class="ml-2 text-xs text-gray-400 dark:text-slate-500">{{ $history->changed_at?->format('d M Y H:i') }}</span>
                </div>
                <a href="{{ $downloadUrl }}"
                    class="inline-flex items-center gap-1 px-3 py-2 bg-blue-600 text-white text-sm font-semibold tracking-wide rounded-md hover:bg-blue-700 transition">
                    {{ __('Unduh') }}
                </a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($document->isPdf())
                    <iframe src="{{ $rawUrl }}" title="{{ $docName }}"
                        class="w-full" style="height: 80vh; border: 0;"></iframe>
                @elseif ($document->isImage())
                    <div class="p-4 flex justify-center bg-gray-50 dark:bg-slate-800/50">
                        <img src="{{ $rawUrl }}" alt="{{ $docName }}"
                            class="max-w-full h-auto rounded-md shadow" />
                    </div>
                @else
                    <div class="p-10 text-center">
                        <p class="text-sm text-gray-600 dark:text-slate-400">
                            {{ __('Pratinjau tidak tersedia untuk tipe berkas ini') }}
                            (<span class="uppercase font-medium">{{ $docExt ?? '—' }}</span>).
                        </p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">
                            {{ __('Silakan unduh berkas untuk membukanya di aplikasi yang sesuai.') }}
                        </p>
                        <a href="{{ $downloadUrl }}"
                            class="mt-4 inline-flex items-center gap-1 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-indigo-700 transition">
                            {{ __('Unduh Dokumen') }}
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>