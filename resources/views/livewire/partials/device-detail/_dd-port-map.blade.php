{{--
    Port map: visual chassis device dengan port-port dalam dua baris (top/bottom).
    Include _dd-port-slot.blade.php untuk setiap port.

    Filter controls:
    - togglePortFilter  → Show All / Physical Only
    - togglePortSelector → buka modal pilih port per-nama

    Parameters:
        $portMap        — dari $this->port_map (Livewire computed)
        $showAllPorts   — bool, Livewire property
--}}
@php $portMap = $this->port_map; @endphp
@if(!empty($portMap['top']) || !empty($portMap['bottom']))
<div class="nova-card p-4 mb-6">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-200 flex items-center gap-2">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
            Port Map
        </h3>
        <div class="flex items-center gap-3 text-xs flex-wrap">
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-sm bg-emerald-500"></span> Up</span>
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-sm bg-red-500"></span> Down</span>
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-sm bg-gray-400"></span> Unknown</span>
            <button wire:click="togglePortFilter"
                    class="cursor-pointer ml-2 inline-flex items-center gap-1 rounded border border-gray-300 dark:border-slate-600 px-2 py-1 text-xs text-gray-600 dark:text-slate-300 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                {{ $showAllPorts ? 'Physical Only' : 'Show All' }}
            </button>
            <button wire:click="togglePortSelector"
                    class="cursor-pointer inline-flex items-center gap-1 rounded border border-gray-300 dark:border-slate-600 px-2 py-1 text-xs text-gray-600 dark:text-slate-300 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Select Ports
            </button>
        </div>
    </div>

    {{-- Device chassis frame --}}
    <div class="rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 overflow-x-auto">
        <div class="p-4" style="min-width: {{ max(8, count($portMap['top'])) * 62 }}px">
            {{-- Top row labels (above ports) --}}
            @if(!empty($portMap['top']))
            <div class="flex gap-1 mb-1">
                @foreach($portMap['top'] as $slot)
                <div class="w-[58px] shrink-0 text-center text-[10px] text-gray-500 dark:text-slate-400 font-mono truncate" title="{{ $slot['name'] }}">{{ $slot['name'] }}</div>
                @endforeach
            </div>
            @endif

            {{-- Top row ports --}}
            @if(!empty($portMap['top']))
            <div class="flex gap-1 mb-1">
                @foreach($portMap['top'] as $slot)
                    @include('livewire.partials.device-detail._dd-port-slot', ['slot' => $slot, 'rowPos' => 'top'])
                @endforeach
            </div>
            @endif

            {{-- Separator --}}
            <div class="my-1 border-t border-gray-200 dark:border-slate-700"></div>

            {{-- Bottom row ports --}}
            @if(!empty($portMap['bottom']))
            <div class="flex gap-1 mb-1">
                @foreach($portMap['bottom'] as $slot)
                    @include('livewire.partials.device-detail._dd-port-slot', ['slot' => $slot, 'rowPos' => 'bottom'])
                @endforeach
            </div>
            @endif

            {{-- Bottom row labels (below ports) --}}
            @if(!empty($portMap['bottom']))
            <div class="flex gap-1 mt-1">
                @foreach($portMap['bottom'] as $slot)
                <div class="w-[58px] shrink-0 text-center text-[10px] text-gray-500 dark:text-slate-400 font-mono truncate" title="{{ $slot['name'] }}">{{ $slot['name'] }}</div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endif
