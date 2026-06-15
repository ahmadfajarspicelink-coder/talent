<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-nova-dark">{{ __('Client') }}</h2>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8 space-y-6">

        {{-- Judul --}}
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-nova-dark">{{ __('Client') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Daftar client aktif (order yang sudah complete).') }}</p>
        </div>

        {{-- Flash message --}}
        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="nova-card p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-slate-700">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Provider/Vendor') }}</th>
                            <!-- <th class="px-3 py-2 font-medium">{{ __('Vendor') }}</th> -->
                            <th class="px-3 py-2 font-medium">{{ __('Bandwidth') }}</th>
                            <th class="px-3 py-2 font-medium text-right">{{ __('MRC') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Kontrak') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Status Kontrak') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Order') }}</th>
                            <th class="px-3 py-2 font-medium text-center">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($clients as $client)
                            @php($o = $client->latestCompletedOrder)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-middle">
                                    <div class="font-semibold text-gray-900 dark:text-slate-100">{{ $client->name }}</div>
                                    @if ($client->address)
                                        <div class="text-xs text-gray-400 dark:text-slate-500">{{ $client->address }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">
                                    <div>{{ $o->provider?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">&darr; {{ $o->vendor?->name ?? '—' }}</div>
                                </td>
                                <!-- <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $o?->provider?->name ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $o?->vendor?->name ?? '—' }}</td> -->
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $o?->bandwidth_label ?: '—' }}</td>
                                <td class="px-3 py-3 align-middle text-right tabular-nums">
                                    <x-rupiah :value="$o?->margin_mrc" />
                                </td>
                                <td class="px-3 py-3 align-middle text-gray-500 dark:text-slate-400">
                                    @if ($o && $o->contract_months)
                                        <div class="font-medium text-gray-700 dark:text-slate-300">{{ $o->contract_months }} {{ __('bulan') }}</div>
                                        @if ($o->contract_start_date && $o->contract_end_date)
                                            <div class="text-xs text-gray-400 dark:text-slate-500">
                                                {{ $o->contract_start_date->format('j/n/Y') }} – {{ $o->contract_end_date->format('j/n/Y') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    @if ($o && $o->contract_active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-950/60 dark:text-green-300">{{ __('Aktif') }}</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-slate-300">{{ __('Tidak Aktif') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    @if ($o)
                                        <a href="{{ route('orders.show', $o) }}"
                                            class="font-medium text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300">{{ $o->display_number }}</a>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <div class="flex items-center justify-center gap-2">
                                        <form method="POST" action="{{ route('clients.upgrade', $client) }}"
                                            onsubmit="return confirm('{{ __('Buka order upgrade untuk') }} {{ $client->name }}?');">
                                            @csrf
                                            <button type="submit"
                                                class="cursor-pointer inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-950/60">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                </svg>
                                                {{ __('Upgrade') }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('clients.dismantle', $client) }}"
                                            onsubmit="return confirm('{{ __('Bongkar (dismantle) layanan') }} {{ $client->name }}? {{ __('Client akan keluar dari daftar aktif.') }}');">
                                            @csrf
                                            <button type="submit"
                                                class="cursor-pointer inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-950/60">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                {{ __('Dismantle') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Belum ada client aktif. Client akan muncul di sini setelah Order-nya berstatus Complete.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat Dismantle --}}
        <div>
            <h2 class="text-lg font-bold tracking-tight text-nova-dark">{{ __('Riwayat Dismantle') }}</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Layanan client yang sudah dibongkar.') }}</p>
        </div>

        <div class="nova-card p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-slate-700">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Provider/Vendfor') }}</th>
                            <!-- <th class="px-3 py-2 font-medium">{{ __('Vendor') }}</th> -->
                            <th class="px-3 py-2 font-medium">{{ __('Bandwidth') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Tanggal Dismantle') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Order') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($dismantled as $d)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-middle font-semibold text-gray-900 dark:text-slate-100">{{ $d->client?->name ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">
                                    <div>{{ $o->provider?->name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 dark:text-slate-500">&darr; {{ $o->vendor?->name ?? '—' }}</div>
                                </td>
                                <!-- <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $d->provider?->name ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $d->vendor?->name ?? '—' }}</td> -->
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $d->bandwidth_label ?: '—' }}</td>
                                <td class="px-3 py-3 align-middle text-gray-500 dark:text-slate-400">{{ $d->dismantled_at?->format('j/n/Y H:i') ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle">
                                    <a href="{{ route('orders.show', $d) }}"
                                        class="font-medium text-blue-600 hover:text-blue-800 hover:underline dark:text-blue-400 dark:hover:text-blue-300">{{ $d->display_number }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Belum ada riwayat dismantle.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
