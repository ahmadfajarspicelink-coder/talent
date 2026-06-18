{{--
    Detail Order — Alur Pemesanan 9 tahap.

    Kiri: timeline tahap. Tiap tahap menampilkan data tersimpan (untuk tahap
    yang sudah selesai) atau form input (untuk tahap berikutnya yang harus
    diselesaikan). Tombol "Tandai Selesai" baru aktif bila semua field tahap
    terisi (gating sisi klien + validasi server di OrderController).

    Kanan: panel ringkas yang terisi otomatis dari data tahap — Detail Order,
    Kontrak Client, dan Harga & Margin.

    Dokumen: setiap OrderStatusHistory dapat memiliki banyak OrderDocument
    (maks 5 × 5 MB). Daftar dokumen ditampilkan per tahap dengan tombol
    pratinjau & hapus individu; form upload menerima multi-file.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('orders.index') }}"
                class="text-gray-400 hover:text-gray-700" title="{{ __('Kembali') }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->display_number }}</h2>
                <p class="text-sm text-gray-500">{{ $order->client?->name ?? '—' }}</p>
            </div>
        </div>
    </x-slot>

    @php($currentIndex = $statusService->indexOf($order->status))
    @php($maxUploadMb = 5)
    @php($maxDocuments = 5)

    <div class="py-8">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Banner: order upgrade dari order asal --}}
            @if ($order->is_upgrade && $order->parentOrder)
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 dark:border-indigo-800 dark:bg-indigo-950/40">
                    <div class="flex items-center gap-2 text-sm text-indigo-800 dark:text-indigo-300">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                        <span>
                            {{ __('Order upgrade dari') }}
                            <a href="{{ route('orders.show', $order->parentOrder) }}" class="font-semibold underline hover:text-indigo-900 dark:hover:text-indigo-200">{{ $order->parentOrder->display_number }}</a>.
                            {{ __('Layanan sebelumnya:') }}
                            <span class="font-medium">{{ $order->parentOrder->bandwidth_label ?: '—' }}</span>,
                            {{ __('MRC') }} <span class="font-medium"><x-rupiah :value="$order->parentOrder->provider_mrc" /></span>.
                        </span>
                    </div>
                </div>
            @endif

            {{-- Banner: layanan sudah dibongkar --}}
            @if ($order->is_dismantled)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                    {{ __('Layanan ini telah dibongkar (dismantle)') }}
                    @if ($order->dismantled_at)
                        {{ __('pada') }} {{ $order->dismantled_at->format('j/n/Y H:i') }}
                    @endif.
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- ============ KIRI: Alur Pemesanan ============ --}}
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Alur Pemesanan') }}</h3>

                        <div class="space-y-3">
                            @foreach (\App\Services\OrderStatusService::STATUSES as $i => $stage)
                                @php($isDone = $i < $currentIndex)
                                @php($isCurrent = $i === $currentIndex)
                                @php($isNext = $i === $currentIndex + 1)
                                @php($isReached = $i <= $currentIndex)
                                @php($history = $statusHistories->where('status', $stage)->sortByDesc('id')->first())

                                <div class="rounded-lg border p-4
                                    @if ($isDone) border-green-200 bg-green-50/40 dark:border-green-800 dark:bg-green-950/30
                                    @elseif ($isCurrent) border-green-300 bg-green-50/60 dark:border-green-700 dark:bg-green-950/40
                                    @elseif ($isNext) border-indigo-300 bg-indigo-50/40 dark:border-indigo-800 dark:bg-indigo-950/30
                                    @else border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50 @endif">
                                    <div class="flex items-start gap-3">
                                        {{-- Penanda tahap --}}
                                        <div class="mt-0.5">
                                            @if ($isReached)
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $isCurrent ? 'bg-indigo-600' : 'bg-green-500' }} text-white">
                                                    @if ($isCurrent)
                                                        <span class="text-xs font-semibold">{{ $i + 1 }}</span>
                                                    @else
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                        </svg>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 {{ $isNext ? 'border-indigo-400 text-indigo-500 dark:border-indigo-500 dark:text-indigo-400' : 'border-gray-300 text-gray-400 dark:border-slate-600 dark:text-slate-400' }} text-xs font-semibold">
                                                    {{ $i + 1 }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900">{{ $statusService->title($stage) }}</p>
                                            <p class="text-xs text-gray-500">{{ $statusService->description($stage) }}</p>

                                            {{-- Nilai tersimpan (tahap yang sudah dilewati/aktif) --}}
                                            @if ($isReached)
                                                @include('orders.partials.stage-summary', ['order' => $order, 'stage' => $stage])
                                            @endif

                                            {{-- Daftar dokumen terlampir (multi) --}}
                                            @if ($history && $history->documents->count() > 0)
                                                <div class="mt-2 space-y-1">
                                                    @php($docCount = $history->documents->count())
                                                    <p class="text-xs text-gray-500 dark:text-slate-400">
                                                        {{ __('Dokumen terlampir (:count/:max)', ['count' => $docCount, 'max' => $maxDocuments]) }}
                                                    </p>
                                                    @foreach ($history->documents as $doc)
                                                        <div class="flex items-center justify-between rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900/50">
                                                            <a href="{{ route('orders.documents.preview', [$order, $doc]) }}"
                                                                class="inline-flex items-center gap-1 text-sm text-gray-700 hover:text-indigo-700 hover:underline truncate dark:text-slate-200 dark:hover:text-indigo-400">
                                                                <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                                </svg>
                                                                <span class="truncate">{{ $doc->document_name ?? __('dokumen') }}</span>
                                                                <span class="ml-1 text-xs uppercase text-gray-400 dark:text-slate-500">{{ $doc->documentExtension() }}</span>
                                                                @if ($doc->size)
                                                                    <span class="ml-1 text-xs text-gray-400 dark:text-slate-500">{{ $doc->size_mb }} MB</span>
                                                                @endif
                                                            </a>
                                                            <form method="POST" action="{{ route('orders.documents.destroy', [$order, $doc]) }}"
                                                                onsubmit="return confirm('{{ __('Hapus dokumen ini?') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="ml-3 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="{{ __('Hapus') }}">
                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Upload dokumen (tahap yang sudah tercapai, selama belum penuh) --}}
                                            @if ($isReached)
                                                @php($existingDocs = $history ? $history->documents->count() : 0)
                                                @if ($existingDocs < $maxDocuments)
                                                    <form method="POST" action="{{ route('orders.documents.store', $order) }}"
                                                        enctype="multipart/form-data" class="mt-2 flex flex-wrap items-center gap-2"
                                                        data-multi-upload
                                                        data-existing="{{ $existingDocs }}"
                                                        data-max="{{ $maxDocuments }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="{{ $stage }}" />
                                                        <label class="sr-only" for="documents-{{ $stage }}">Dokumen</label>
                                                        <input id="documents-{{ $stage }}" name="documents[]" type="file" multiple
                                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                                            class="text-xs text-gray-600 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:text-slate-400 file:dark:bg-slate-700 file:dark:text-slate-200 hover:file:dark:bg-slate-600" />
                                                        <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                            {{ $existingDocs > 0 ? __('Tambah dokumen') : __('Upload dokumen') }}
                                                        </button>
                                                        <p class="w-full text-[11px] text-gray-400 dark:text-slate-500">
                                                            {{ __('Maks :maxDocs × :maxMb MB. Sisa slot: :remaining', ['maxDocs' => $maxDocuments, 'maxMb' => $maxUploadMb, 'remaining' => $maxDocuments - $existingDocs]) }}
                                                        </p>
                                                    </form>
                                                @else
                                                    <p class="mt-2 text-[11px] text-amber-600 dark:text-amber-400">
                                                        {{ __('Batas :max dokumen untuk tahap ini sudah tercapai. Hapus salah satu untuk menambah.', ['max' => $maxDocuments]) }}
                                                    </p>
                                                @endif
                                            @endif

                                            {{-- Form penyelesaian tahap berikutnya --}}
                                            @if ($isNext)
                                                @php(
                                                    $providerBandwidth = $stage === 'PO_Vendor' && $order->bandwidth !== null && $order->bandwidth !== ''
                                                        ? (int) $order->bandwidth
                                                        : null
                                                )
                                                <form method="POST" action="{{ route('orders.advanceStatus', $order) }}"
                                                    enctype="multipart/form-data" class="mt-3 rounded-md border border-gray-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900/50"
                                                    x-data='{ ok: false, providerBw: {{ $providerBandwidth === null ? 'null' : (int) $providerBandwidth }}, bwMismatch() { if (this.providerBw === null) return false; const el = this.$root.querySelector("input[name=vendor_bandwidth]"); if (!el) return false; const v = String(el.value).trim(); if (v === "") return false; return parseInt(v, 10) !== this.providerBw; }, vendorBw() { const el = this.$root.querySelector("input[name=vendor_bandwidth]"); return el ? String(el.value).trim() : ""; }, check() { this.ok = [...this.$root.querySelectorAll("[data-req]")].every(el => String(el.value).trim() !== ""); } }'
                                                    x-init="check()" @input="check()">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $stage }}" />

                                                    @include('orders.partials.stage-fields', ['order' => $order, 'stage' => $stage, 'errors' => $errors])

                                                    <div class="mt-3">
                                                        <button type="submit" :disabled="!ok"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md text-white transition"
                                                            :class="!ok ? 'bg-indigo-300 cursor-not-allowed dark:bg-indigo-800' : (bwMismatch ? 'bg-yellow-500 hover:bg-yellow-600 cursor-pointer dark:bg-yellow-500 dark:hover:bg-yellow-400' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer dark:bg-indigo-500 dark:hover:bg-indigo-400')">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                            </svg>
                                                            {{ __('Tandai Selesai') }}
                                                        </button>
                                                        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ __('Isi semua field untuk dapat menandai selesai.') }}</p>

                                                        {{-- Peringatan mismatch bandwidth (PO_Provider vs PO_Vendor) --}}
                                                        <div x-show="bwMismatch" x-cloak
                                                            class="mt-2 rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-xs text-yellow-800 dark:border-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300">
                                                            <p class="font-semibold">{{ __('Peringatan: Bandwidth tidak sama') }}</p>
                                                            <p class="mt-0.5">
                                                                {{ __('PO Provider') }}: <span class="font-semibold" x-text="providerBw + ' Mbps'"></span>
                                                                &rarr;
                                                                {{ __('PO Vendor') }}: <span class="font-semibold" x-text="vendorBw() + ' Mbps'"></span>.
                                                            </p>
                                                            <p class="mt-1">{{ __('Nilai berbeda, namun Anda tetap dapat melanjutkan.') }}</p>
                                                        </div>
                                                    </div>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ============ KANAN: Panel ============ --}}
                <div class="space-y-6">
                    @include('orders.partials.panel-detail', ['order' => $order])
                    @include('orders.partials.panel-kontrak', ['order' => $order])
                    @include('orders.partials.panel-margin', ['order' => $order])
                </div>

            </div>
        </div>
    </div>

    {{-- Validasi ukuran & jumlah dokumen di sisi klien (batas aplikasi
        5 × 5 MB). Dipakai untuk input type=file multiple dengan atribut
        data-multi-upload (form upload khusus per tahap) maupun
        data-multi-doc (input di dalam stage-fields). --}}
    @push('scripts')
        <script>
            (function () {
                const MAX_MB = {{ $maxUploadMb }};
                const MAX_FILES = {{ $maxDocuments }};
                const MAX_BYTES = MAX_MB * 1024 * 1024;
                const fmt = (b) => (b / (1024 * 1024)).toFixed(1);

                function warnEl(form) {
                    let el = form.querySelector('.upload-size-warning');
                    if (!el) {
                        el = document.createElement('p');
                        el.className = 'upload-size-warning w-full mt-1 text-xs text-red-600';
                        form.appendChild(el);
                    }
                    return el;
                }

                function clearWarn(form) {
                    const existing = form.querySelector('.upload-size-warning');
                    if (existing) existing.textContent = '';
                }

                // Validasi satu input type=file (single atau multi).
                function check(input) {
                    const form = input.closest('form');
                    if (!form) return true;

                    const files = input.files ? Array.from(input.files) : [];
                    if (files.length === 0) {
                        clearWarn(form);
                        return true;
                    }

                    // Batas jumlah: existing (di atribut data-existing) + baru.
                    const existing = parseInt(input.closest('[data-multi-upload]')?.dataset.existing || '0', 10);
                    const maxAttr = parseInt(input.closest('[data-multi-upload]')?.dataset.max || String(MAX_FILES), 10);
                    if (files.length + existing > maxAttr) {
                        warnEl(form).textContent = 'Jumlah file (' + (files.length + existing) + ') melebihi batas ' + maxAttr + ' untuk tahap ini. Kelebihan: ' + (files.length + existing - maxAttr) + '.';
                        return false;
                    }

                    // Batas ukuran per file.
                    const tooBig = files.filter((f) => f.size > MAX_BYTES);
                    if (tooBig.length > 0) {
                        const names = tooBig.map((f) => f.name + ' (' + fmt(f.size) + ' MB)').join(', ');
                        warnEl(form).textContent = 'Ukuran file melebihi batas ' + MAX_MB + ' MB: ' + names;
                        return false;
                    }

                    clearWarn(form);
                    return true;
                }

                document.querySelectorAll('input[type="file"][name="documents[]"]').forEach(function (input) {
                    input.addEventListener('change', function () { if (!check(input)) input.value = ''; });
                    const form = input.closest('form');
                    if (form) form.addEventListener('submit', function (e) { if (!check(input)) e.preventDefault(); });
                });
            })();
        </script>
    @endpush
</x-app-layout>