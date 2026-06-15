{{--
    Port selector modal: panel muncul saat user klik "Select Ports".
    Tampilkan tombol per-nama interface. Klik toggle select.

    Actions (Livewire):
    - savePortFilter()    → simpan pilihan ke device
    - clearPortFilter()   → reset pilihan
    - togglePortSelector()→ tutup modal
    - togglePort($name)   → toggle 1 port

    Parameters:
        $showPortSelector — bool, Livewire property
        $interfaces       — Collection<NetworkInterface>
        $selectedPorts    — array of port names
--}}
@if($showPortSelector)
<div class="nova-card p-4 mb-6">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-slate-200">Select Ports to Display</h3>
        <div class="flex gap-2">
            <button wire:click="savePortFilter"
                    class="cursor-pointer inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1 text-xs font-medium text-white shadow-sm hover:bg-blue-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 23.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 5.25c0 .372-.186.716-.495.912l-3.75 2.36a.75.75 0 01-.51.094l-3.75-.75a.75.75 0 01-.495-.912v-1.13c0-.431.288-.81.708-.917l3.6-.72a.75.75 0 01.59.135l3.5 2.1a.75.75 0 01.342.642V5.25z"/></svg>
                Save
            </button>
            <button wire:click="clearPortFilter"
                    class="cursor-pointer inline-flex items-center gap-1 rounded-lg bg-red-50 dark:bg-red-950/40 px-3 py-1 text-xs font-medium text-red-600 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950/60">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Clear
            </button>
            <button wire:click="togglePortSelector"
                    class="cursor-pointer inline-flex items-center gap-1 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1 text-xs font-medium text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Close
            </button>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($interfaces as $iface)
        <button wire:click="togglePort('{{ $iface->if_name }}')"
                class="cursor-pointer inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium border transition
                    {{ in_array($iface->if_name, $selectedPorts)
                        ? 'bg-blue-600 border-blue-500 text-white shadow-sm'
                        : 'bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-300 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400' }}">
            {{ $iface->if_name }}
            <span class="inline-block h-1.5 w-1.5 rounded-full {{ $iface->if_oper_status === 'up' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
        </button>
        @endforeach
    </div>
</div>
@endif
