<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-nova-dark">{{ __('Finance') }}</h2>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8 space-y-6">

        {{-- Judul --}}
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-nova-dark">{{ __('Finance') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Rekap margin OTC & MRC dari client aktif.') }}</p>
        </div>

        {{-- Kartu ringkasan --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Total Margin OTC --}}
            <div class="nova-card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Total Margin OTC / bulan') }}</p>
                        <p class="mt-1 text-2xl font-bold text-nova-dark"><x-rupiah :value="$summary['otc']" /></p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950/40 text-orange-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m0 0a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 9" />
                        </svg>
                    </span>
                </div>
            </div>

            {{-- Total Margin MRC / bulan --}}
            <div class="nova-card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Total Margin MRC / bulan') }}</p>
                        <p class="mt-1 text-2xl font-bold text-nova-dark"><x-rupiah :value="$summary['mrc']" /></p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.306a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </span>
                </div>
            </div>

            {{-- Total Margin / bulan --}}
            <div class="nova-card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Total Margin / bulan') }}</p>
                        <p class="mt-1 text-2xl font-bold text-nova-dark"><x-rupiah :value="$summary['otc'] + $summary['mrc']" /></p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        {{-- Tabel rincian per order --}}
        <div class="nova-card p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('No. Order') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Provider/Vendor') }}</th>
                            <th class="px-3 py-2 font-medium text-right">{{ __('Margin OTC') }}</th>
                            <th class="px-3 py-2 font-medium text-right">{{ __('Margin MRC') }}</th>
                            <th class="px-3 py-2 font-medium text-right">{{ __('Total Revenue Kontrak') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($orderMargins as $row)
                            @php($o = $row['order'])
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-middle">
                                    <a href="{{ route('orders.show', $o) }}"
                                        class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">{{ $o->display_number }}</a>
                                </td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $o->client?->name ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">
                                    <div>{{ $o->provider?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">&darr; {{ $o->vendor?->name ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-3 align-middle text-right tabular-nums">
                                    <x-rupiah :value="$row['otc']" />
                                </td>
                                <td class="px-3 py-3 align-middle text-right tabular-nums">
                                    <x-rupiah :value="$row['mrc']" />
                                </td>
                                <td class="px-3 py-3 align-middle text-right tabular-nums font-semibold text-indigo-700 dark:text-indigo-300">
                                    <x-rupiah :value="$row['total_revenue']" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Belum ada client aktif untuk dilaporkan.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
