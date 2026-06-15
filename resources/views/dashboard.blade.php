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

        {{-- Order Terbaru --}}
        <div class="nova-card p-6">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-nova-dark">{{ __('Order Terbaru') }}</h3>
                <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    {{ __('Lihat semua') }} &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('No. Order') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                            <th class="px-3 py-2 font-medium text-right">{{ __('Diperbarui') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-middle">
                                    <a href="{{ route('orders.show', $order) }}"
                                        class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline">{{ $order->display_number }}</a>
                                </td>
                                <td class="px-3 py-3 align-middle text-gray-700 dark:text-slate-300">{{ $order->client?->name ?? '—' }}</td>
                                <td class="px-3 py-3 align-middle">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-950/40 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                                        {{ $statusService->title($order->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-middle text-right text-gray-500 dark:text-slate-400">
                                    {{ $order->updated_at?->format('d/m/Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Belum ada order.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
