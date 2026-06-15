<div class="p-6">
    {{-- ===================== SUMMARY CARDS ===================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        {{-- Total Devices --}}
        <div class="nova-card p-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Total Devices</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $totalDevices }}</p>
                </div>
            </div>
        </div>

        {{-- Online --}}
        <div class="nova-card p-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Online</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $totalUp }}</p>
                </div>
            </div>
        </div>

        {{-- Offline --}}
        <div class="nova-card p-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Offline</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $totalDown }}</p>
                </div>
            </div>
        </div>

        {{-- Avg CPU --}}
        <div class="nova-card p-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Avg CPU</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $avgCpu }}%</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== ALERTS ===================== --}}
    @if(count($alerts) > 0)
    <div class="mb-4 nova-card border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 p-4">
        <h3 class="text-amber-700 dark:text-amber-300 font-semibold mb-2 flex items-center gap-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            Perubahan Status Terakhir
        </h3>
        <ul class="text-sm space-y-1">
            @foreach($alerts as $alert)
                <li class="text-amber-700 dark:text-amber-300">• {{ $alert }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ===================== ACTION BAR ===================== --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Devices</h2>
        <div class="flex gap-2">
            <a href="/network/devices"
               class="nova-nav-item rounded-lg border border-gray-200 dark:border-slate-700 px-4 py-2 text-sm font-medium">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Device Manager
            </a>
            <a href="/network/top-traffic"
               class="nova-nav-item rounded-lg border border-gray-200 dark:border-slate-700 px-4 py-2 text-sm font-medium">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                Top Traffic
            </a>
            <button wire:click="pollNow" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:opacity-50">
                <span wire:loading.remove class="flex items-center gap-1.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    Poll Now
                </span>
                <span wire:loading class="flex items-center gap-1">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Polling...
                </span>
            </button>
        </div>
    </div>

    {{-- ===================== DEVICES TABLE ===================== --}}
    <div class="nova-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Device</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">IP</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">CPU</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Memory</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Uptime</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Ports</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Last Polled</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($devices as $device)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-slate-100">{{ $device->name }}</div>
                            @if($device->vendor)
                                <div class="text-xs text-gray-500 dark:text-slate-400">{{ $device->vendor }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $device->ip_address }}</td>
                        <td class="px-4 py-3">
                            @if($device->status === 'online')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Online
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-1 text-xs font-medium text-red-700 dark:text-red-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Offline
                                </span>
                            @endif
                        </td>
                        {{-- CPU --}}
                        <td class="px-4 py-3">
                            @if($device->cpu_usage !== null)
                                @if($device->cpu_usage > 80)
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $device->cpu_usage }}%</span>
                                @elseif($device->cpu_usage > 50)
                                    <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ $device->cpu_usage }}%</span>
                                @else
                                    <span class="text-sm text-emerald-600 dark:text-emerald-400">{{ $device->cpu_usage }}%</span>
                                @endif
                            @else
                                <span class="text-sm text-gray-400 dark:text-slate-500">-</span>
                            @endif
                        </td>
                        {{-- Memory --}}
                        <td class="px-4 py-3">
                            @if($device->memory_total)
                                @php $memPct = $device->memory_percent; @endphp
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $memPct > 80 ? 'bg-red-500' : ($memPct > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                             style="width: {{ $memPct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-600 dark:text-slate-400">{{ $memPct }}%</span>
                                </div>
                                <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                                    {{ \App\Models\Device::formatBytes($device->memory_used) }} / {{ \App\Models\Device::formatBytes($device->memory_total) }}
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-slate-500">-</span>
                            @endif
                        </td>
                        {{-- Uptime --}}
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-slate-400">{{ $device->uptime_formatted }}</td>
                        {{-- Ports --}}
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ $device->interfaces->where('if_oper_status', 'up')->count() }}</span>
                            <span class="text-sm text-gray-400 dark:text-slate-500">/</span>
                            <span class="text-sm text-gray-500 dark:text-slate-400">{{ $device->interfaces->count() }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400 dark:text-slate-500">
                            {{ $device->last_polled_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="/network/devices/{{ $device->id }}"
                               class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition">
                                Detail
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <svg class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" />
                            </svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Belum ada device</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Tambah via Device Manager atau Poll</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
