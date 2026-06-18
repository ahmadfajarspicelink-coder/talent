{{-- Panel Detail Order. Parameter: $order. --}}
<div class="bg-white shadow-sm sm:rounded-lg p-5 dark:bg-slate-800">
    <h3 class="text-sm font-semibold text-gray-900 mb-3 dark:text-slate-100">{{ __('Detail Order') }}</h3>
    <dl class="space-y-2 text-sm">
        <div class="flex items-start justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Provider') }}</dt>
            <dd class="text-right text-gray-900 dark:text-slate-100">{{ $order->provider?->name ?? '—' }}</dd>
        </div>
        <div class="flex items-start justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Vendor') }}</dt>
            <dd class="text-right text-gray-900 dark:text-slate-100">{{ $order->vendor?->name ?? '—' }}</dd>
        </div>
         <div class="flex items-start justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Jenis Layanan') }}</dt>
            <dd class="text-right text-gray-900 dark:text-slate-100">{{ $order->package?->name ?? '—' }}</dd>
        </div>
        <div class="flex items-start justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Bandwidth') }}</dt>
            <dd class="text-right text-gray-900 dark:text-slate-100">
                {{ $order->bandwidth_label ?: '—' }}
                @if ($order->vendor_bandwidth_label)
                    <span class="text-xs text-gray-500 dark:text-slate-400"> / {{ $order->vendor_bandwidth_label }}</span>
                @endif
            </dd>
        </div>
        @if ($order->has_bandwidth_mismatch)
            <div class="rounded-md border border-yellow-300 bg-yellow-50 px-2.5 py-2 text-xs text-yellow-800 dark:border-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300">
                <div class="flex items-start gap-1.5">
                    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <div>
                        <p class="font-semibold">{{ __('Alert: Bandwidth tidak sama') }}</p>
                        <p>{{ __('PO Provider') }} {{ $order->bandwidth_label }} &rarr; {{ __('PO Vendor') }} {{ $order->vendor_bandwidth_label }}.</p>
                    </div>
                </div>
            </div>
        @endif
        <div class="flex items-start justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Alamat') }}</dt>
            <dd class="text-right text-gray-900 dark:text-slate-100">{{ $order->client?->address ?: '—' }}</dd>
        </div>
        <div class="flex items-start justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Catatan') }}</dt>
            <dd class="text-right text-gray-900 dark:text-slate-100">{{ $order->note ?: '—' }}</dd>
        </div>
    </dl>
</div>
