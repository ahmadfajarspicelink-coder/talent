<div class="p-6">
    {{-- ===================== HEADER ===================== --}}
    <div class="mb-6">
        <a href="/network" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-slate-400 hover:text-blue-600 transition mb-1">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Dashboard
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-slate-100">Top-N Traffic</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Interface dengan traffic tertinggi</p>
    </div>

    {{-- ===================== FILTERS ===================== --}}
    <div class="nova-card p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Device --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Device</label>
                <select wire:model.live="selectedDevice"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="all">Semua Device</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->ip_address }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Top Count --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Top</label>
                <select wire:model.live="topCount"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="5">Top 5</option>
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="50">Top 50</option>
                </select>
            </div>

            {{-- Sort --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Urutkan</label>
                <select wire:model.live="sortBy"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="total">Total Bytes (kumulatif)</option>
                    <option value="rate">Rate Total (B/s)</option>
                    <option value="inbound">Rate IN (B/s)</option>
                    <option value="outbound">Rate OUT (B/s)</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===================== BAR CHART ===================== --}}
    @if(count($topInterfaces) > 0)
    <div class="nova-card p-4 mb-6" x-data="topChart()" x-init="init()">
        <h3 class="mb-3 text-sm font-semibold text-gray-600 dark:text-slate-400">Traffic Rate Overview</h3>
        <canvas id="topChart" height="200"></canvas>
    </div>

    <script>
    function topChart() {
        return {
            chart: null,
            data: @json($topInterfaces),
            init() {
                this.$nextTick(() => {
                    const ctx = document.getElementById('topChart').getContext('2d');
                    const labels = this.data.map(d => d.device_name + ' → ' + d.if_name);

                    this.chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'IN (B/s)',
                                    data: this.data.map(d => d.rate_in),
                                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                    borderColor: '#10b981',
                                    borderWidth: 1,
                                },
                                {
                                    label: 'OUT (B/s)',
                                    data: this.data.map(d => d.rate_out),
                                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                    borderColor: '#3b82f6',
                                    borderWidth: 1,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            indexAxis: 'y',
                            scales: {
                                x: {
                                    stacked: false,
                                    ticks: { color: '#6b7280' },
                                    grid: { color: '#e5e7eb' }
                                },
                                y: {
                                    ticks: { color: '#374151', font: { size: 11 } },
                                    grid: { display: false }
                                }
                            },
                            plugins: {
                                legend: { labels: { color: '#374151' } }
                            }
                        }
                    });
                });
            }
        }
    }
    </script>
    @endif

    {{-- ===================== TOP-N TABLE ===================== --}}
    <div class="nova-card overflow-hidden">
        <div class="border-b border-gray-200 dark:border-slate-700 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">Top {{ $topCount }} Interfaces</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 text-left">
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">#</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Device</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Interface</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Alias</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Rate IN</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Rate OUT</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Total IN</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Total OUT</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Utilization</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($topInterfaces as $idx => $iface)
                    @php
                        $util = \App\Livewire\TopTraffic::utilization($iface['rate_total'], $iface['speed']);
                    @endphp
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-400 dark:text-slate-500">{{ $idx + 1 }}</td>
                        <td class="px-4 py-2">
                            <div class="text-xs font-medium text-gray-900 dark:text-slate-100">{{ $iface['device_name'] }}</div>
                            <div class="font-mono text-xs text-gray-500 dark:text-slate-400">{{ $iface['ip'] }}</div>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-slate-300">{{ $iface['if_name'] }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">{{ $iface['if_alias'] ?: '-' }}</td>
                        {{-- Rate IN --}}
                        <td class="px-4 py-2">
                            <span class="font-mono text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                {{ \App\Livewire\TopTraffic::formatRate($iface['rate_in']) }}
                            </span>
                        </td>
                        {{-- Rate OUT --}}
                        <td class="px-4 py-2">
                            <span class="font-mono text-xs font-medium text-blue-600 dark:text-blue-400">
                                {{ \App\Livewire\TopTraffic::formatRate($iface['rate_out']) }}
                            </span>
                        </td>
                        {{-- Total IN --}}
                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-slate-400">
                            {{ \App\Livewire\TopTraffic::formatBytes($iface['in_octets']) }}
                        </td>
                        {{-- Total OUT --}}
                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-slate-400">
                            {{ \App\Livewire\TopTraffic::formatBytes($iface['out_octets']) }}
                        </td>
                        {{-- Utilization --}}
                        <td class="px-4 py-2">
                            @if($iface['speed'] > 0)
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $util > 80 ? 'bg-red-500' : ($util > 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                             style="width: {{ min($util, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs {{ $util > 80 ? 'text-red-600 font-semibold' : ($util > 50 ? 'text-amber-600' : 'text-gray-500') }}">
                                        {{ $util }}%
                                    </span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 dark:text-slate-500">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <svg class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Belum ada data traffic</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Jalankan polling terlebih dahulu</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
