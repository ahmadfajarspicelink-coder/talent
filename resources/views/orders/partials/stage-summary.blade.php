{{--
    Ringkasan nilai tersimpan untuk sebuah tahap (ditampilkan pada tahap yang
    sudah tercapai/aktif). Parameter: $order, $stage.

    Tiap nilai dirender sebagai item yang mengalir (flex-wrap) agar pada layar
    lebar tampil satu baris dan otomatis turun ke baris berikutnya pada layar
    sempit (responsif).
--}}
@php($money = fn ($v) => $v === null ? '—' : 'Rp '.number_format((int) $v, 0, ',', '.'))

@switch($stage)
    @case('Penawaran')
        @if ($order->offer_number)
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-700 dark:text-slate-200">
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Nomor Penawaran') }}:</span> {{ $order->offer_number }}</span>
            </div>
        @endif
        @break

    @case('PO_Provider')
        @if ($order->po_provider_number || $order->provider_otc !== null || $order->bandwidth)
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-700 dark:text-slate-200">
                @if ($order->package)
                    <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Jenis Layanan') }}:</span> {{ $order->package->name }}</span>
                @endif
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Nomor PO') }}:</span> {{ $order->po_provider_number ?: '—' }}</span>
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('OTC Provider') }}:</span> {{ $money($order->provider_otc) }}</span>
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('MRC Provider') }}:</span> {{ $money($order->provider_mrc) }}</span>
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Bandwidth (Mbps)') }}:</span> {{ $order->bandwidth_label ?: '—' }}</span>
            </div>
        @endif
        @break

    @case('PO_Vendor')
        @if ($order->po_vendor_number || $order->vendor_otc !== null)
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-700 dark:text-slate-200">
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Nomor PO') }}:</span> {{ $order->po_vendor_number ?: '—' }}</span>
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('OTC Vendor') }}:</span> {{ $money($order->vendor_otc) }}</span>
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('MRC Vendor') }}:</span> {{ $money($order->vendor_mrc) }}</span>
            </div>
        @endif
        @break

    @case('BAA_BAST')
        @if ($order->baa_number || $order->bast_number)
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-700 dark:text-slate-200">
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Nomor BAA') }}:</span> {{ $order->baa_number ?: '—' }}</span>
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Nomor BAST') }}:</span> {{ $order->bast_number ?: '—' }}</span>
            </div>
        @endif

        {{-- Tombol Generate & Download BAA/BAST --}}
        @if ($order->bastDocuments->count() > 0)
            <div class="mt-3 rounded-md border border-green-200 bg-green-50 p-3 space-y-2">
                <p class="text-xs font-semibold text-green-800">{{ __('Dokumen BAA & BAST tersedia') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($order->bastDocuments as $doc)
                        <a href="{{ route('orders.bast.download', [$order, $doc]) }}"
                            class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-slate-200 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ $doc->type }}
                        </a>
                    @endforeach
                    <a href="{{ route('orders.bast.download-all', $order) }}"
                        class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-300 hover:bg-indigo-100">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        {{ __('Download Semua (ZIP)') }}
                    </a>
                </div>
                <form method="POST" action="{{ route('orders.bast.generate', $order) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 underline">
                        {{ __('Generate Ulang') }}
                    </button>
                </form>
            </div>
        @else
            @if ($order->baa_number && $order->bast_number)
                <form method="POST" action="{{ route('orders.bast.generate', $order) }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        {{ __('Generate BAA & BAST') }}
                    </button>
                </form>
            @endif
        @endif
        @break

    @case('Client_Aktif')
        @if ($order->contract_months)
            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-700 dark:text-slate-200">
                <span><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Durasi Kontrak (bulan, min 12)') }}:</span> {{ $order->contract_months }}</span>
            </div>
        @endif
        @break
@endswitch
