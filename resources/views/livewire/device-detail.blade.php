{{--
    Device detail Livewire component.
    Setiap section extracted ke partials di livewire/partials/device-detail/
    untuk maintainability. Lihat masing-masing partial untuk dokumentasi.

    Partial:
    - _dd-header.blade.php          → header (breadcrumb, name, status pill, poll button)
    - _dd-metrics.blade.php         → CPU/Memory/Uptime cards (3-kolom)
    - _dd-port-map.blade.php        → visual chassis (port top + bottom rows)
    - _dd-port-slot.blade.php       → single port slot (dipakai oleh port-map)
    - _dd-port-selector.blade.php   → modal pilih port
    - _dd-history-charts.blade.php  → CPU & Memory line charts
    - _dd-interfaces-table.blade.php → tabel semua interface
    - _dd-traffic-chart.blade.php   → line chart untuk interface yg dipilih

    Parameters (Livewire properties):
    - $device              App\Models\Device
    - $interfaces          Collection<NetworkInterface>
    - $showAllPorts        bool
    - $showPortSelector    bool
    - $selectedPorts       array
    - $selectedInterface   int|null
    - $cpuHistory          array
    - $memHistory          array
    - $chartData           array
--}}
<div class="p-6">
    {{-- ===================== ERROR FLASH ===================== --}}
    @if(session('error'))
    <div class="mb-4 nova-card border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/40 p-4 text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    @include('livewire.partials.device-detail._dd-header', ['device' => $device])
    @include('livewire.partials.device-detail._dd-metrics', ['device' => $device])
    @include('livewire.partials.device-detail._dd-port-map', ['showAllPorts' => $showAllPorts])
    @include('livewire.partials.device-detail._dd-port-selector', [
        'showPortSelector' => $showPortSelector,
        'interfaces' => $interfaces,
        'selectedPorts' => $selectedPorts,
    ])
    @include('livewire.partials.device-detail._dd-history-charts', [
        'cpuHistory' => $cpuHistory,
        'memHistory' => $memHistory,
    ])
    @include('livewire.partials.device-detail._dd-interfaces-table', [
        'interfaces' => $interfaces,
        'selectedInterface' => $selectedInterface,
    ])
    @include('livewire.partials.device-detail._dd-traffic-chart', [
        'selectedInterface' => $selectedInterface,
        'chartData' => $chartData,
    ])
</div>
