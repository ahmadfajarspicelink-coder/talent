{{--
    Single port slot (kotak chassis) yang reusable untuk top row & bottom row.
    Klik slot akan trigger selectInterface() di parent Livewire component.

    Parameters:
        $slot   — array with keys: id, name, status, admin, speed
        $rowPos — 'top' | 'bottom' (untuk spacing label position)
--}}
<div wire:click="selectInterface({{ $slot['id'] }})"
     class="w-[58px] shrink-0 cursor-pointer transition-all duration-200 hover:scale-105 hover:z-10"
     title="{{ $slot['name'] }} — {{ ucfirst($slot['status']) }}">
    <div class="aspect-square rounded-md flex flex-col items-center justify-center border transition-all
        @if($slot['status'] === 'up')
            bg-emerald-500 border-emerald-400 shadow-lg shadow-emerald-500/30
        @elseif($slot['status'] === 'down' && $slot['admin'] === 'up')
            bg-red-500 border-red-400 shadow-lg shadow-red-500/30
        @else
            bg-gray-300 border-gray-400
        @endif">
        <span class="text-[8px] font-bold text-white drop-shadow leading-tight">{{ $slot['name'] }}</span>
        @if($slot['speed'])
            <span class="text-[7px] text-white/70">{{ \App\Models\Device::formatSpeed($slot['speed']) }}</span>
        @endif
    </div>
</div>
