<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-nova-dark">{{ __('Dashboard') }}</h2>
    </x-slot>

    @php
        $role = auth()->user()->role ?? '';
        $policy = app(\App\Services\ModuleAccessPolicy::class);
        $canFinance = $policy->canAccess($role, 'finance');
        $canClient = $policy->canAccess($role, 'client');
    @endphp

    <div class="p-4 sm:p-6 lg:p-8 space-y-6">

        {{-- Judul --}}
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-nova-dark">{{ __('Dashboard') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Ringkasan operasional broker ISP.') }}</p>
        </div>

        {{-- Kartu statistik --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Order Berjalan --}}
            <div class="nova-card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Order Berjalan') }}</p>
                        <p class="mt-1 text-2xl font-bold text-nova-dark">{{ $ordersInProgress }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </span>
                </div>
            </div>

            @if ($canClient)
                {{-- Client Aktif --}}
                <div class="nova-card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Client Aktif') }}</p>
                            <p class="mt-1 text-2xl font-bold text-nova-dark">{{ $activeClients }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950/40 text-green-600 dark:text-green-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </span>
                    </div>
                </div>
            @else
                {{-- Order Selesai (untuk staff) --}}
                <div class="nova-card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Order Selesai') }}</p>
                            <p class="mt-1 text-2xl font-bold text-nova-dark">{{ $ordersCompleted }}</p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950/40 text-green-600 dark:text-green-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                </div>
            @endif

            @if ($canFinance)
                {{-- Margin OTC --}}
                <div class="nova-card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Margin OTC / bulan') }}</p>
                            <p class="mt-1 text-2xl font-bold text-nova-dark"><x-rupiah :value="$marginOtc" /></p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-950/40 text-orange-500">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m0 0a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 9" />
                            </svg>
                        </span>
                    </div>
                </div>

                {{-- Margin MRC --}}
                <div class="nova-card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Margin MRC / bulan') }}</p>
                            <p class="mt-1 text-2xl font-bold text-nova-dark"><x-rupiah :value="$marginMrc" /></p>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h12M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                            </svg>
                        </span>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===================== Tracking Client ===================== --}}
        @php
            // Peta warna per jenis event untuk baris spotlight di bawah.
            $trackSpot = [
                'order'     => ['text' => 'text-blue-700 dark:text-blue-300'],
                'logdown'   => ['text' => 'text-amber-700 dark:text-amber-300'],
                'upgrade'   => ['text' => 'text-indigo-700 dark:text-indigo-300'],
                'dismantle' => ['text' => 'text-red-700 dark:text-red-300'],
            ];
        @endphp

        <div class="nova-card p-4 sm:p-6">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-nova-dark">{{ __('Tracking Client') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400">
                        {{ __('Riwayat lengkap aktivitas client aktif: order, logdown, upgrade bandwidth, dan dismantle.') }}
                    </p>
                </div>
                @if (($policy->canAccess($role, 'client') ?? false) && $trackingClients->count() > 0)
                    <a href="{{ route('clients.index') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 whitespace-nowrap">
                        {{ __('Lihat semua client') }} &rarr;
                    </a>
                @endif
            </div>

            @if ($trackingClients->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/40 p-6 text-center">
                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-slate-400">
                        {{ __('Belum ada client aktif. Tracking akan muncul setelah order pertama berstatus Client_Aktif.') }}
                    </p>
                </div>
            @else
                {{-- Tabel tracking client: header tetap di atas, tiap client --}}
                {{-- satu baris utama + sub-baris daftar aktivitas terbaru. --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-slate-700 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                                <th class="py-2 pl-2 pr-3 w-10">{{ __('No') }}</th>
                                <th class="py-2 px-3">{{ __('User') }}</th>
                                <th class="py-2 px-3">{{ __('Status') }}</th>
                                <th class="py-2 px-3 text-center">{{ __('Order') }}</th>
                                <th class="py-2 px-3 text-center">{{ __('Logdown') }}</th>
                                <th class="py-2 px-3 text-center">{{ __('Upgrade') }}</th>
                                <th class="py-2 px-3 text-center">{{ __('Dismantle') }}</th>
                                <th class="py-2 px-3 pr-2"></th>
                            </tr>
                        </thead>
                        {{-- Setiap client dibungkus <tbody> sendiri agar main-row + --}}
                        {{-- sub-row aktivitas tetap 1 grup visual (tanpa garis pemisah). --}}
                        {{-- Garis hanya muncul di antara tbody = pemisah antar client. --}}
                        @foreach ($trackingClients as $i => $row)
                            @php($client = $row['client'])
                            <tbody class="border-b border-gray-100 dark:border-slate-700 [&:last-child]:border-b-0">
                                <tr class="align-top hover:bg-gray-50/60 dark:hover:bg-slate-800/40">
                                    <td class="py-2 pl-2 pr-3 text-gray-500 dark:text-slate-400">{{ $i + 1 }}</td>
                                    <td class="py-2 px-3">
                                        <a href="{{ route('clients.tracking', $client) }}"
                                            class="font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:underline">
                                            {{ $client->name }}
                                        </a>
                                        @if ($client->address)
                                            <p class="text-[11px] text-gray-500 dark:text-slate-400 truncate max-w-xs">{{ $client->address }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3">
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-800 dark:bg-green-950/60 dark:text-green-300">
                                            {{ __('Aktif') }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-center font-semibold text-gray-900 dark:text-slate-100">{{ $row['total_orders'] }}</td>
                                    <td class="py-2 px-3 text-center font-semibold text-gray-900 dark:text-slate-100">{{ $row['total_logdown'] }}</td>
                                    <td class="py-2 px-3 text-center font-semibold text-gray-900 dark:text-slate-100">{{ $row['total_upgrade'] }}</td>
                                    <td class="py-2 px-3 text-center font-semibold text-gray-900 dark:text-slate-100">{{ $row['total_dismantle'] }}</td>
                                    <td class="py-2 px-3 pr-2 text-right">
                                        <a href="{{ route('clients.tracking', $client) }}"
                                            class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                        </a>
                                    </td>
                                </tr>
                                {{-- Sub-baris: daftar aktivitas terbaru (spotlight) --}}
                                @if ($row['spotlight']->isNotEmpty())
                                    <tr class="bg-gray-50/40 dark:bg-slate-800/30">
                                        <td></td>
                                        <td colspan="7" class="py-1.5 pl-3 pr-2">
                                            <ul class="space-y-0.5">
                                                @foreach ($row['spotlight'] as $ev)
                                                    @php($sp = $trackSpot[$ev['type']] ?? $trackSpot['order'])
                                                    <li class="flex items-baseline gap-3 text-[12px]">
                                                        <span class="w-2 text-gray-400 dark:text-slate-500">-</span>
                                                        <span class="font-medium {{ $sp['text'] }}">{{ $ev['title'] }}</span>
                                                        <span class="ml-auto font-mono text-gray-500 dark:text-slate-400">{{ $ev['date']->format('d/m/Y H:i') }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>