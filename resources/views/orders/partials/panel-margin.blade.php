{{-- Panel Harga & Margin. Parameter: $order. Terisi otomatis dari tahap PO. --}}
<div class="bg-white shadow-sm sm:rounded-lg p-5 dark:bg-slate-800">
    <h3 class="text-sm font-semibold text-gray-900 mb-3 dark:text-slate-100">{{ __('Harga & Margin') }}</h3>
    <dl class="space-y-2 text-sm">
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('OTC Provider') }}</dt>
            <dd class="text-gray-900 tabular-nums dark:text-slate-100"><x-rupiah :value="$order->provider_otc" /></dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('MRC Provider') }}</dt>
            <dd class="text-gray-900 tabular-nums dark:text-slate-100"><x-rupiah :value="$order->provider_mrc" /></dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('OTC Vendor') }}</dt>
            <dd class="text-gray-900 tabular-nums dark:text-slate-100"><x-rupiah :value="$order->vendor_otc" /></dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('MRC Vendor') }}</dt>
            <dd class="text-gray-900 tabular-nums dark:text-slate-100"><x-rupiah :value="$order->vendor_mrc" /></dd>
        </div>

        <div class="border-t border-gray-200 my-2 dark:border-slate-700"></div>

        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Margin OTC') }}</dt>
            <dd class="font-semibold text-green-600 tabular-nums dark:text-green-400"><x-rupiah :value="$order->margin_otc" /></dd>
        </div>
        <div class="flex items-center justify-between gap-3">
            <dt class="text-gray-500 dark:text-slate-400">{{ __('Margin MRC / bulan') }}</dt>
            <dd class="font-semibold text-green-600 tabular-nums dark:text-green-400"><x-rupiah :value="$order->margin_mrc" /></dd>
        </div>
    </dl>
    <p class="mt-3 text-xs text-gray-400 dark:text-slate-500">{{ __('Otomatis terisi dari tahap PO Provider & PO Vendor.') }}</p>
</div>
