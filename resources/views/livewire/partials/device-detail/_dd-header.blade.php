{{--
    Device header: breadcrumb back link, name, IP/vendor/model/location meta,
    online/offline pill, and Poll button.

    Parameters:
        $device — App\Models\Device
--}}
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <a href="/network" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 transition mb-1">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Dashboard
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-slate-100">{{ $device->name }}</h1>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400 mt-0.5 flex-wrap">
            <span class="font-mono text-xs">{{ $device->ip_address }}</span>
            @if($device->vendor)<span>·</span><span>{{ $device->vendor }}</span>@endif
            @if($device->model)<span>·</span><span>{{ $device->model }}</span>@endif
            @if($device->location)
                <span>·</span>
                <span class="inline-flex items-center gap-0.5">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    {{ $device->location }}
                </span>
            @endif
        </div>
    </div>
    <div class="flex items-center gap-3">
        @if($device->status === 'online')
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Online
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/40 px-3 py-1 text-sm font-medium text-red-700 dark:text-red-300">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                Offline
            </span>
        @endif
        <button wire:click="pollDevice" wire:loading.attr="disabled"
                class="cursor-pointer inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="pollDevice" class="flex items-center gap-1.5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                Poll
            </span>
            <span wire:loading wire:target="pollDevice" class="flex items-center gap-1">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Polling...
            </span>
        </button>
    </div>
</div>
