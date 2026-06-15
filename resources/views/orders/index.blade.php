{{--
    Daftar Order (task 12.3) — R5.5, R6.8, R6.10.

    Menampilkan tiap Order beserta Client, Provider, Vendor, dan Status_Order
    saat ini (R5.5), lengkap dengan Indikator_Progress visual yang
    merepresentasikan Persentase_Progress (R6.8). Persentase dihitung dari
    Status_Order saat ini lewat $statusService->progressPercent(), sehingga
    tampilan otomatis selaras dengan status terbaru (R6.10). Setiap baris
    menautkan ke detail Order (orders.show).

    Indikator_Progress dirender lewat komponen <x-progress-indicator> berupa
    garis/bar progres diagonal (lihat resources/views/components/progress-indicator.blade.php).
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-nova-dark">{{ __('Order') }}</h2>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8 space-y-6">

        @php($isAdmin = (auth()->user()->role ?? '') === 'admin')

        {{-- Judul --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-nova-dark">{{ __('Order') }}</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Daftar order beserta status dan progres pengerjaan.') }}</p>
            </div>
            <a href="{{ route('orders.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white tracking-wide shadow-nova-sm hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Buat Order') }}
            </a>
        </div>

        <div class="nova-card p-6">
            <div class="overflow-x-auto">
                <table class="ts-table min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('No. Order') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Provider/Vendor') }}</th>
                            <!-- <th class="px-3 py-2 font-medium">{{ __('Vendor') }}</th> -->
                            <th class="px-3 py-2 font-medium text-right">{{ __('Margin OTC') }}</th>
                            <th class="px-3 py-2 font-medium text-right">{{ __('Margin MRC') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Progress') }}</th>
                            @if ($isAdmin)
                                <th class="px-3 py-2 font-medium text-center">{{ __('Aksi') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($orders as $order)
                            @php($percent = $statusService->progressPercent($order->status))
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-middle whitespace-nowrap font-medium">
                                    <a href="{{ route('orders.show', $order) }}"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">{{ $order->display_number }}</a>
                                </td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $order->client?->name ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">
                                    <div>{{ $order->provider?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">&darr; {{ $order->vendor?->name ?? '—' }}</div>
                                </td>
                                <!-- <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $order->provider?->name ?? '—' }}</td> -->
                                <!-- <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $order->vendor?->name ?? '—' }}</td> -->
                                <td class="px-3 py-3 align-middle text-right tabular-nums whitespace-nowrap">
                                    <x-rupiah :value="$order->margin_otc" />
                                </td>
                                <td class="px-3 py-3 align-middle text-right tabular-nums whitespace-nowrap">
                                    <x-rupiah :value="$order->margin_mrc" />
                                </td>
                                <td class="px-3 py-3 align-middle whitespace-nowrap">
                                    @if ($order->is_dismantled)
                                        <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                                            {{ $statusService->title($order->status) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-950/40 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                                            {{ $statusService->title($order->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    @if ($order->is_dismantled)
                                        <span class="text-xs text-gray-400 dark:text-slate-500">—</span>
                                    @else
                                        <div class="w-32"><x-progress-indicator :percent="$percent" /></div>
                                    @endif
                                </td>
                                @if ($isAdmin)
                                    <td class="px-3 py-3 align-middle text-center">
                                        <button type="submit" form="delete-order-{{ $order->id }}" title="{{ __('Hapus') }}"
                                            class="inline-flex items-center justify-center rounded-md p-1.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/60 hover:text-red-700 dark:hover:text-red-300">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 9 : 8 }}" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Belum ada order.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Form hapus diletakkan DI LUAR tabel (menghindari bug
                 foster-parenting <form> di dalam <table>). Tombol di
                 dalam sel menautkan ke form ini via atribut form="". --}}
            @if ($isAdmin)
                @foreach ($orders as $order)
                    <form id="delete-order-{{ $order->id }}" method="POST"
                        action="{{ route('orders.destroy', $order) }}" class="hidden"
                        onsubmit="return confirm('{{ __('Hapus order') }} {{ $order->display_number }}?');">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        </div>

    </div>
</x-app-layout>
