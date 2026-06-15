{{--
    Traffic history chart untuk interface yang dipilih.
    Muncul hanya jika $selectedInterface ada dan $chartData > 0.

    Parameters:
        $selectedInterface — int|null, Livewire property
        $chartData         — array of {time, in, out}
--}}
@if($selectedInterface && count($chartData) > 0)
<div class="nova-card p-4 mb-6" x-data="trafficChart()" x-init="init()">
    <h3 class="mb-3 text-sm font-semibold text-gray-600 dark:text-slate-300">Traffic History — Selected Interface</h3>
    <canvas id="trafficChart" height="200"></canvas>
</div>

<script>
function trafficChart() {
    return {
        chart: null,
        data: @json($chartData),
        init() {
            this.$nextTick(() => {
                const ctx = document.getElementById('trafficChart').getContext('2d');
                if (this.chart) this.chart.destroy();

                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.data.map(d => d.time),
                        datasets: [
                            {
                                label: 'IN (bytes)',
                                data: this.data.map(d => d.in),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                fill: true, tension: 0.3,
                            },
                            {
                                label: 'OUT (bytes)',
                                data: this.data.map(d => d.out),
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                                fill: true, tension: 0.3,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: { ticks: { color: '#6b7280' }, grid: { color: '#e5e7eb' } },
                            y: { ticks: { color: '#6b7280' }, grid: { color: '#e5e7eb' } }
                        },
                        plugins: { legend: { labels: { color: '#374151' } } }
                    }
                });
            });
        }
    }
}
</script>
@endif
