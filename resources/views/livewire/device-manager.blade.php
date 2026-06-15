<div class="p-6">
    {{-- ===================== HEADER ===================== --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <a href="/network" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-slate-400 hover:text-blue-600 transition mb-1">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Dashboard
            </a>
            <h1 class="text-xl font-bold text-gray-900 dark:text-slate-100">Device Manager</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">Kelola perangkat jaringan</p>
        </div>
        <button wire:click="showAddForm"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Tambah Device
        </button>
    </div>

    {{-- ===================== FLASH MESSAGE ===================== --}}
    @if(session('message'))
    <div class="mb-4 nova-card border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-sm text-emerald-700 dark:text-emerald-300 flex items-center gap-2">
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('message') }}
    </div>
    @endif

    {{-- ===================== ADD / EDIT FORM ===================== --}}
    @if($showForm)
    <div class="nova-card p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">{{ $editId ? 'Edit Device' : 'Tambah Device Baru' }}</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Name --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Nama Device *</label>
                <input type="text" wire:model="name" placeholder="Switch Lt.2"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @error('name') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            {{-- IP --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">IP Address *</label>
                <div class="flex gap-2">
                    <input type="text" wire:model="ip_address" placeholder="192.168.1.1"
                           class="flex-1 rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <button type="button" wire:click="testConnection"
                            class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-gray-300 dark:border-slate-600 bg-white px-3 py-2 text-xs font-medium text-gray-700 dark:text-slate-300 shadow-sm hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/></svg>
                        Test
                    </button>
                </div>
                @error('ip_address') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            {{-- Community --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">SNMP Community *</label>
                <input type="text" wire:model="snmp_community" placeholder="public"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                @error('snmp_community') <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
            </div>

            {{-- Version --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">SNMP Version</label>
                <select wire:model="snmp_version"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="2c">v2c</option>
                    <option value="1">v1</option>
                    <option value="3">v3</option>
                </select>
            </div>

            {{-- Vendor --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Vendor</label>
                <select wire:model="vendor"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Auto-detect</option>
                    <option value="mikrotik">Mikrotik</option>
                    <option value="cisco">Cisco</option>
                    <option value="huawei">Huawei</option>
                    <option value="olt_huawei">Huawei OLT</option>
                    <option value="olt_zte">ZTE OLT</option>
                    <option value="generic">Generic</option>
                </select>
            </div>

            {{-- Model --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Model</label>
                <input type="text" wire:model="model" placeholder="CRS326-24G-2S+"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            {{-- Location --}}
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-slate-400">Lokasi</label>
                <input type="text" wire:model="location" placeholder="Ruang Server Lt.2"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        {{-- Test Result --}}
        @if($testResult)
        <div class="mt-4 rounded-lg border p-3 text-sm {{ $testResult['success'] ? 'border-emerald-200 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700' : 'border-red-200 bg-red-50 dark:bg-red-950/40 text-red-700' }}">
            @if($testResult['success'])
                <span class="font-semibold">Connected!</span><br>
                Hostname: {{ $testResult['sysName'] }}<br>
                sysDescr: {{ Str::limit($testResult['sysDescr'], 80) }}
            @else
                {{ $testResult['message'] }}
            @endif
        </div>
        @endif

        {{-- Actions --}}
        <div class="mt-4 flex gap-2">
            <button wire:click="saveDevice"
                    class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 23.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 5.25c0 .372-.186.716-.495.912l-3.75 2.36a.75.75 0 01-.51.094l-3.75-.75a.75.75 0 01-.495-.912v-1.13c0-.431.288-.81.708-.917l3.6-.72a.75.75 0 01.59.135l3.5 2.1a.75.75 0 01.342.642V5.25z"/></svg>
                {{ $editId ? 'Update' : 'Simpan' }}
            </button>
            <button wire:click="cancelForm"
                    class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white px-6 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 shadow-sm transition hover:bg-gray-50 dark:hover:bg-slate-800/50">
                Batal
            </button>
        </div>
    </div>
    @endif

    {{-- ===================== DEVICES TABLE ===================== --}}
    <div class="nova-card overflow-hidden">
        <div class="border-b border-gray-200 dark:border-slate-700 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">Registered Devices ({{ $devices->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 text-left">
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Name</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">IP</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Vendor</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Community</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Status</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Interfaces</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Location</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Last Polled</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($devices as $device)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-2">
                            <div class="font-medium text-gray-900 dark:text-slate-100">{{ $device->name }}</div>
                            @if($device->model)
                                <div class="text-xs text-gray-500 dark:text-slate-400">{{ $device->model }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $device->ip_address }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-block rounded bg-gray-100 dark:bg-slate-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-slate-400">
                                {{ $device->vendor ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $device->snmp_community }}</td>
                        <td class="px-4 py-2">
                            @if($device->status === 'online')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Online
                                </span>
                            @elseif($device->status === 'offline')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-1 text-xs font-medium text-red-700 dark:text-red-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Offline
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 dark:bg-slate-700 px-2.5 py-1 text-xs font-medium text-gray-500 dark:text-slate-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                    Unknown
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">{{ $device->interfaces_count }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">{{ $device->location ?: '-' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-400 dark:text-slate-500">
                            {{ $device->last_polled_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex gap-1">
                                <a href="/network/devices/{{ $device->id }}"
                                   class="inline-flex items-center gap-0.5 rounded px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 transition hover:bg-blue-50">
                                    Detail
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                </a>
                                <button wire:click="editDevice({{ $device->id }})"
                                        class="rounded px-2 py-1 text-xs font-medium text-amber-600 dark:text-amber-400 transition hover:bg-amber-50">
                                    Edit
                                </button>
                                <button wire:click="deleteDevice({{ $device->id }})"
                                        wire:confirm="Yakin hapus {{ $device->name }}?"
                                        class="rounded px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 transition hover:bg-red-50 dark:hover:bg-red-950/60">
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <svg class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z"/></svg>
                            <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Belum ada device terdaftar</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Klik "Tambah Device" atau jalankan <code class="rounded bg-gray-100 dark:bg-slate-700 px-1.5 py-0.5">php artisan snmp:discover</code></p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
