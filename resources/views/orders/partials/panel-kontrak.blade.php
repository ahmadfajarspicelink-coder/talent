{{-- Panel Kontrak Client. Parameter: $order. --}}
<div class="bg-white shadow-sm sm:rounded-lg p-5 dark:bg-slate-800">
    <h3 class="text-sm font-semibold text-gray-900 mb-3 dark:text-slate-100">{{ __('Kontrak Client') }}</h3>

    @if ($order->contract_start_date)
        <dl class="space-y-2 text-sm">
            <div class="flex items-center justify-between gap-3">
                <dt class="text-gray-500 dark:text-slate-400">{{ __('Durasi') }}</dt>
                <dd class="text-gray-900 dark:text-slate-100">{{ $order->contract_months }} {{ __('bulan') }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-gray-500 dark:text-slate-400">{{ __('Mulai') }}</dt>
                <dd class="text-gray-900 dark:text-slate-100">{{ $order->contract_start_date->format('j/n/Y') }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-gray-500 dark:text-slate-400">{{ __('Berakhir') }}</dt>
                <dd class="text-gray-900 dark:text-slate-100">{{ $order->contract_end_date?->format('j/n/Y') ?? '—' }}</dd>
            </div>
        </dl>
    @else
        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Kontrak akan aktif setelah tahap "Client Aktif" diselesaikan.') }}</p>
    @endif
</div>
