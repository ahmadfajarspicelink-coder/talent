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
            <dd class="text-right text-gray-900 dark:text-slate-100">{{ $order->bandwidth_label ?: '—' }}</dd>
        </div>
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
