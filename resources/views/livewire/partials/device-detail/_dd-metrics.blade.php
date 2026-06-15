{{--
    Metric cards: CPU usage, Memory usage, Uptime. Tampil di grid 3-kolom.

    Reusable color helper (di-include per card):
    - $value > 80 → red
    - $value > 50 → amber
    - else        → emerald

    Parameters:
        $device — App\Models\Device
--}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    {{-- CPU --}}
    <div class="nova-card p-5">
        <div class="text-sm font-medium text-gray-500 dark:text-slate-400 mb-2">CPU Usage</div>
        @if($device->cpu_usage !== null)
            @php $cpu = $device->cpu_usage; @endphp
            <div class="flex items-end gap-2">
                <span class="text-4xl font-bold {{ $cpu > 80 ? 'text-red-600 dark:text-red-400' : ($cpu > 50 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">{{ $cpu }}</span>
                <span class="mb-1 text-sm text-gray-400 dark:text-slate-500">%</span>
            </div>
            <div class="mt-3 w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all {{ $cpu > 80 ? 'bg-red-500' : ($cpu > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                     style="width: {{ $cpu }}%"></div>
            </div>
        @else
            <span class="text-2xl text-gray-300 dark:text-slate-600">-</span>
        @endif
    </div>

    {{-- Memory --}}
    <div class="nova-card p-5">
        <div class="text-sm font-medium text-gray-500 dark:text-slate-400 mb-2">Memory Usage</div>
        @if($device->memory_total)
            @php $memPct = $device->memory_percent; @endphp
            <div class="flex items-end gap-2">
                <span class="text-4xl font-bold {{ $memPct > 80 ? 'text-red-600 dark:text-red-400' : ($memPct > 50 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">{{ $memPct }}</span>
                <span class="mb-1 text-sm text-gray-400 dark:text-slate-500">%</span>
            </div>
            <div class="mt-3 w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all {{ $memPct > 80 ? 'bg-red-500' : ($memPct > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                     style="width: {{ $memPct }}%"></div>
            </div>
            <div class="mt-2 text-xs text-gray-400 dark:text-slate-500">
                {{ \App\Models\Device::formatBytes($device->memory_used) }} / {{ \App\Models\Device::formatBytes($device->memory_total) }}
            </div>
        @else
            <span class="text-2xl text-gray-300 dark:text-slate-600">-</span>
        @endif
    </div>

    {{-- Uptime --}}
    <div class="nova-card p-5">
        <div class="text-sm font-medium text-gray-500 dark:text-slate-400 mb-2">Uptime</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $device->uptime_formatted }}</div>
        <div class="mt-2 text-xs text-gray-400 dark:text-slate-500">
            Last polled: {{ $device->last_polled_at?->format('Y-m-d H:i:s') ?? 'Never' }}
        </div>
    </div>
</div>
