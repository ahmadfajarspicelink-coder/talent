{{--
    CPU & Memory history charts (Chart.js via Alpine.js).
    Ditampilkan hanya jika history punya >1 data point.

    Alpine functions:
    - cpuChart() — line chart CPU % orange
    - memChart() — line chart Memory % purple

    PENTING: Alpine functions harus dideklarasikan SEBELUM x-data,
    tapi dalam @include partial, script harus di-load SETELAH canvas
    ada di DOM. Solusi: gunakan init() dengan $nextTick.

    Parameters:
        $cpuHistory — array of {time, value}
        $memHistory — array of {time, percent}
--}}
@if(count($cpuHistory) > 1)
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="nova-card p-4" x-data="cpuChart()" x-init="init()">
        <h3 class="mb-3 text-sm font-semibold text-gray-600 dark:text-slate-300">CPU History</h3>
        <canvas id="cpuChart" height="180"></canvas>
    </div>
    <div class="nova-card p-4" x-data="memChart()" x-init="init()">
        <h3 class="mb-3 text-sm font-semibold text-gray-600 dark:text-slate-300">Memory History</h3>
        <canvas id="memChart" height="180"></canvas>
    </div>
</div>

<script>
function cpuChart() {
    return {
        chart: null,
        data: @json($cpuHistory),
        init() {
            this.$nextTick(() => {
                const ctx = document.getElementById('cpuChart').getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.data.map(d => d.time),
                        datasets: [{
                            label: 'CPU %',
                            data: this.data.map(d => d.value),
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.08)',
                            fill: true, tension: 0.3, pointRadius: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { min: 0, max: 100, ticks: { color: '#6b7280' }, grid: { color: '#e5e7eb' } },
                            x: { ticks: { color: '#6b7280', maxTicksLimit: 10 }, grid: { color: '#e5e7eb' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            });
        }
    }
}
function memChart() {
    return {
        chart: null,
        data: @json($memHistory),
        init() {
            this.$nextTick(() => {
                const ctx = document.getElementById('memChart').getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.data.map(d => d.time),
                        datasets: [{
                            label: 'Memory %',
                            data: this.data.map(d => d.percent),
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139,92,246,0.08)',
                            fill: true, tension: 0.3, pointRadius: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { min: 0, max: 100, ticks: { color: '#6b7280' }, grid: { color: '#e5e7eb' } },
                            x: { ticks: { color: '#6b7280', maxTicksLimit: 10 }, grid: { color: '#e5e7eb' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            });
        }
    }
}
</script>
@endif
