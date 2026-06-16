<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500 dark:text-slate-400">{{ __('Ticket') }}</span>
            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <h2 class="text-lg font-semibold text-nova-dark">{{ __('Logdown') }}</h2>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8 space-y-6">

        @php($isAdmin = (auth()->user()->role ?? '') === 'admin')

        {{-- Judul --}}
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-nova-dark">{{ __('Logdown') }}</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">
                {{ __('Catat downtime client aktif: vendor, waktu mulai, waktu pulih, durasi, alasan, dan tindakan.') }}
            </p>
        </div>

        {{-- Flash message --}}
        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                {{ session('error') }}
            </div>
        @endif

        {{-- Form Catat Downtime --}}
        <div class="nova-card p-6">
            <h3 class="text-base font-semibold text-nova-dark mb-4">{{ __('Catat Downtime Baru') }}</h3>

            <form method="POST" action="{{ route('logdown.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Client (select dari client aktif) --}}
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Client') }}</label>
                        <select id="client_id" name="client_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('— Pilih client aktif —') }}</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Client name (alternatif teks bebas) --}}
                    <div>
                        <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ __('Atau nama client (manual)') }}
                        </label>
                        <input id="client_name" name="client_name" type="text" value="{{ old('client_name') }}"
                            placeholder="{{ __('…jika tidak ada di daftar') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        @error('client_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Vendor --}}
                    <div>
                        <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Vendor') }}</label>
                        <select id="vendor_id" name="vendor_id"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('— Pilih vendor —') }}</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Down At --}}
                    <div>
                        <label for="down_at" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Waktu Mulai Down') }}</label>
                        <input id="down_at" name="down_at" type="datetime-local" value="{{ old('down_at', now()->format('Y-m-d\TH:i')) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        @error('down_at')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Up At (opsional — kosongkan bila masih down) --}}
                    <div>
                        <label for="up_at" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                            {{ __('Waktu Pulih (Up)') }}
                            <span class="text-xs font-normal text-gray-400 dark:text-slate-500">{{ __('— opsional, kosongkan bila masih down') }}</span>
                        </label>
                        <input id="up_at" name="up_at" type="datetime-local" value="{{ old('up_at') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                        @error('up_at')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Reason --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Reason / Alasan') }}</label>
                    <textarea id="reason" name="reason" rows="2" placeholder="{{ __('Apa penyebab downtime?') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Action --}}
                <div>
                    <label for="action" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Action / Tindakan') }}</label>
                    <textarea id="action" name="action" rows="2" placeholder="{{ __('Tindakan yang dilakukan untuk memulihkan layanan…') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('action') }}</textarea>
                    @error('action')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white tracking-wide shadow-nova-sm hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('Simpan') }}
                    </button>
                    <p class="text-xs text-gray-500 dark:text-slate-400">
                        {{ __('Durasi dihitung otomatis dari Waktu Pulih − Waktu Mulai Down.') }}
                    </p>
                </div>
            </form>
        </div>

        {{-- Insiden Aktif --}}
        <div>
            <h2 class="text-lg font-bold tracking-tight text-nova-dark">{{ __('Insiden Aktif (Belum Pulih)') }}</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Insiden yang masih berstatus down. Tandai pulih setelah layanan kembali normal.') }}</p>
        </div>

        <div class="nova-card p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('Waktu Down') }}</th>
                            <th class="px-3 py-2 font-medium">{{__('Durasi (saat ini)') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Vendor') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Reason') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Action') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                            <th class="px-3 py-2 font-medium text-center">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($ongoing as $log)
                            <tr x-data="{ editing: false }" class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-top whitespace-nowrap font-medium text-gray-900 dark:text-slate-100">
                                    {{ $log->down_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 whitespace-nowrap">
                                    <span class="text-red-600 dark:text-red-400 font-medium">{{ $log->down_at->diffForHumans(now(), true) }}</span>
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300">
                                    {{ $log->client_label }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300">
                                    {{ $log->vendor?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 max-w-xs">
                                    <div class="line-clamp-2">{{ $log->reason }}</div>
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 max-w-xs">
                                    <div class="line-clamp-2">{{ $log->action ?: '—' }}</div>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                                        {{ __('Down') }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-top text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <form method="POST" action="{{ route('logdown.resolve', $log) }}"
                                            onsubmit="return confirm('{{ __('Tandai insiden ini pulih?') }}');">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100 dark:bg-green-950/40 dark:text-green-300 dark:hover:bg-green-950/60">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                                {{ __('Selesaikan') }}
                                            </button>
                                        </form>
                                        <button type="button" @click="editing = true"
                                            class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-950/60">
                                            {{ __('Edit') }}
                                        </button>
                                        @if ($isAdmin)
                                            <form method="POST" action="{{ route('logdown.destroy', $log) }}"
                                                onsubmit="return confirm('{{ __('Hapus catatan downtime ini?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-950/60">
                                                    {{ __('Hapus') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            {{-- Edit row inline (Alpine.js) --}}
                            <tr x-show="editing" x-cloak>
                                <td colspan="8" class="px-3 py-3 bg-gray-50 dark:bg-slate-800/40">
                                    <form method="POST" action="{{ route('logdown.update', $log) }}"
                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Client') }}</label>
                                            <select name="client_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm">
                                                <option value="">{{ __('— Pilih —') }}</option>
                                                @foreach ($clients as $c)
                                                    <option value="{{ $c->id }}" {{ $log->client_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Vendor') }}</label>
                                            <select name="vendor_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm">
                                                <option value="">{{ __('— Pilih —') }}</option>
                                                @foreach ($vendors as $v)
                                                    <option value="{{ $v->id }}" {{ $log->vendor_id == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Waktu Down') }}</label>
                                            <input name="down_at" type="datetime-local" value="{{ $log->down_at->format('Y-m-d\TH:i') }}"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Waktu Up') }}</label>
                                            <input name="up_at" type="datetime-local" value="{{ $log->up_at?->format('Y-m-d\TH:i') }}"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Atau nama client') }}</label>
                                            <input name="client_name" type="text" value="{{ $log->client_name }}"
                                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm" />
                                        </div>
                                        <div class="sm:col-span-2 lg:col-span-3">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Reason') }}</label>
                                            <textarea name="reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm">{{ $log->reason }}</textarea>
                                        </div>
                                        <div class="sm:col-span-2 lg:col-span-3">
                                            <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Action') }}</label>
                                            <textarea name="action" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm">{{ $log->action }}</textarea>
                                        </div>
                                        <div class="flex gap-2 sm:col-span-2 lg:col-span-4">
                                            <button type="submit"
                                                class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white tracking-wide hover:bg-blue-700 transition">
                                                {{ __('Update') }}
                                            </button>
                                            <button type="button" @click="editing = false"
                                                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 dark:border-slate-600 rounded-md font-semibold text-xs text-gray-700 dark:text-slate-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-slate-800/50 transition">
                                                {{ __('Batal') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Tidak ada insiden aktif. Semua layanan berjalan normal.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat --}}
        <div>
            <h2 class="text-lg font-bold tracking-tight text-nova-dark">{{ __('Riwayat Downtime (Sudah Pulih)') }}</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('100 insiden terakhir yang sudah ditandai pulih, diurutkan dari yang paling baru.') }}</p>
        </div>

        <div class="nova-card p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-slate-400">
                            <th class="px-3 py-2 font-medium">{{ __('Waktu Down') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Waktu Up') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Durasi') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Client') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Vendor') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Reason') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Action') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                            @if ($isAdmin)
                                <th class="px-3 py-2 font-medium text-center">{{ __('Aksi') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($resolved as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-3 align-top whitespace-nowrap font-medium text-gray-900 dark:text-slate-100">
                                    {{ $log->down_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 whitespace-nowrap">
                                    {{ $log->up_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 whitespace-nowrap font-medium">
                                    {{ $log->duration_human ?? '—' }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300">
                                    {{ $log->client_label }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300">
                                    {{ $log->vendor?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 max-w-xs">
                                    <div class="line-clamp-2">{{ $log->reason }}</div>
                                </td>
                                <td class="px-3 py-3 align-top text-gray-700 dark:text-slate-300 max-w-xs">
                                    <div class="line-clamp-2">{{ $log->action ?: '—' }}</div>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-950/60 dark:text-green-300">
                                        {{ __('Selesai') }}
                                    </span>
                                </td>
                                @if ($isAdmin)
                                    <td class="px-3 py-3 align-top text-center">
                                        <form method="POST" action="{{ route('logdown.destroy', $log) }}"
                                            onsubmit="return confirm('{{ __('Hapus catatan downtime ini?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-950/60">
                                                {{ __('Hapus') }}
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 9 : 8 }}" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                    {{ __('Belum ada riwayat downtime.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>