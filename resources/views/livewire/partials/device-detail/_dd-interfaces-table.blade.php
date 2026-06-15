{{--
    Interfaces table — list semua interface device dengan status, speed, traffic.

    Klik baris akan trigger selectInterface() untuk drill-down ke traffic chart.

    Parameters:
        $interfaces       — Collection<NetworkInterface>
        $selectedInterface — int|null, Livewire property
--}}
<div class="nova-card overflow-hidden mb-6">
    <div class="flex items-center justify-between border-b border-gray-200 dark:border-slate-700 px-4 py-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">Interfaces ({{ $interfaces->count() }})</h3>
        <div class="flex items-center gap-3 text-xs">
            <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-gray-600 dark:text-slate-400">{{ $interfaces->where('if_oper_status', 'up')->count() }} Up</span>
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                <span class="text-gray-600 dark:text-slate-400">{{ $interfaces->where('if_oper_status', 'down')->count() }} Down</span>
            </span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 text-left">
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Idx</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Name</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Alias</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Status</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Admin</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Speed</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">IN (bytes)</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">OUT (bytes)</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Errors</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($interfaces as $iface)
                <tr wire:click="selectInterface({{ $iface->id }})"
                    class="cursor-pointer transition hover:bg-gray-50 dark:hover:bg-slate-800/50 {{ $selectedInterface === $iface->id ? 'bg-blue-50/50 dark:bg-blue-950/30' : '' }}">
                    <td class="px-4 py-2 text-xs text-gray-400 dark:text-slate-500">{{ $iface->if_index }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-slate-200">{{ $iface->if_name }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">{{ $iface->if_alias ?: '-' }}</td>
                    <td class="px-4 py-2">
                        @if($iface->if_oper_status === 'up')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Up
                            </span>
                        @elseif($iface->if_oper_status === 'down')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/40 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                Down
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                {{ $iface->if_oper_status }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">{{ $iface->if_admin_status }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">
                        {{ \App\Models\Device::formatSpeed($iface->if_speed) }}
                    </td>
                    <td class="px-4 py-2 font-mono text-xs text-emerald-600 dark:text-emerald-400">{{ number_format($iface->if_in_octets) }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-blue-600 dark:text-blue-400">{{ number_format($iface->if_out_octets) }}</td>
                    <td class="px-4 py-2 text-xs">
                        @if($iface->if_in_errors + $iface->if_out_errors > 0)
                            <span class="font-semibold text-red-600 dark:text-red-400">{{ $iface->if_in_errors + $iface->if_out_errors }}</span>
                        @else
                            <span class="text-gray-400 dark:text-slate-500">0</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
