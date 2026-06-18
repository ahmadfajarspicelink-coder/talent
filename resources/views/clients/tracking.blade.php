{{--
    Tracking Client — view-only riwayat lengkap satu client aktif.

    UI mirip orders/{id} (panel kiri timeline, panel kanan ringkasan),
    tetapi:
      • Tidak ada form edit / upload / tombol lanjut tahap.
      • Sumber event digabung: order (created + status progression),
        logdown (downtime), upgrade bandwidth, dan dismantle.
      • Tiap event punya "spotlight" warna sesuai jenis untuk pembacaan
        sekilas (biru = order, hijau = aktif, kuning = logdown, indigo
        = upgrade, merah = dismantle / masih down).

    Dokumen: setiap perubahan status Order dapat memiliki banyak
    OrderDocument (maks 5 × 5 MB). Ditampilkan sebagai daftar link
    pratinjau/unduh pada timeline.
--}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('clients.index') }}"
                class="text-gray-400 hover:text-gray-700" title="{{ __('Kembali') }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tracking Client') }}</h2>
                <p class="text-sm text-gray-500">{{ $client->name }}</p>
            </div>
        </div>
    </x-slot>

    @php
        // Peta warna spotlight (Tailwind) per jenis event.
        $spotlight = [
            'blue'   => ['border' => 'border-blue-300',   'bg' => 'bg-blue-50/60',   'dot' => 'bg-blue-600',   'text' => 'text-blue-700'],
            'indigo' => ['border' => 'border-indigo-300', 'bg' => 'bg-indigo-50/60', 'dot' => 'bg-indigo-600', 'text' => 'text-indigo-700'],
            'green'  => ['border' => 'border-green-300',  'bg' => 'bg-green-50/60',  'dot' => 'bg-green-600',  'text' => 'text-green-700'],
            'amber'  => ['border' => 'border-amber-300',  'bg' => 'bg-amber-50/60',  'dot' => 'bg-amber-500',  'text' => 'text-amber-700'],
            'red'    => ['border' => 'border-red-300',    'bg' => 'bg-red-50/60',    'dot' => 'bg-red-600',    'text' => 'text-red-700'],
            'slate'  => ['border' => 'border-slate-300',  'bg' => 'bg-slate-50/60',  'dot' => 'bg-slate-500',  'text' => 'text-slate-700'],
            'gray'   => ['border' => 'border-gray-300',   'bg' => 'bg-gray-50',      'dot' => 'bg-gray-500',   'text' => 'text-gray-700'],
        ];

        $typeLabel = [
            'order'     => 'Order',
            'logdown'   => 'Logdown',
            'upgrade'   => 'Upgrade',
            'dismantle' => 'Dismantle',
        ];

        $latestActiveOrder = $orders
            ->where('status', $statusService::FINAL_STATUS)
            ->sortByDesc('updated_at')
            ->first() ?? $orders->sortByDesc('updated_at')->first();
    @endphp

    <div class="py-8">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ===================== CLIENT HEADER ===================== --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 dark:bg-slate-800">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $client->name }}</h1>
                            @if ($client->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-950/60 dark:text-green-300">
                                    {{ __('Aktif') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-slate-700 dark:text-slate-300">
                                    {{ __('Tidak Aktif') }}
                                </span>
                            @endif
                        </div>
                        @if ($client->address)
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $client->address }}</p>
                        @endif
                        <p class="mt-3 text-xs text-gray-500 dark:text-slate-400">
                            {{ __('Total') }}
                            <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $timeline->count() }}</span>
                            {{ __('aktivitas tercatat sejak pertama kali order dibuat.') }}
                        </p>
                    </div>

                    {{-- Navigasi pindah client --}}
                    @if ($activeClients->count() > 1)
                        <form method="GET" action="{{ url()->current() }}" class="w-full md:w-auto">
                            <label for="switch-client" class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Pindah client') }}</label>
                            <select id="switch-client" name="client_id" onchange="if (this.value) window.location = '{{ route('clients.tracking', ['client' => '__ID__']) }}'.replace('__ID__', this.value)"
                                class="mt-1 block w-full md:w-72 rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($activeClients as $ac)
                                    <option value="{{ $ac->id }}" @selected($ac->id === $client->id)>{{ $ac->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>

                {{-- Ringkasan singkat: badge per kategori event --}}
                @php
                    $countByType = $timeline->groupBy('type')->map->count();
                @endphp
                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 dark:bg-blue-950/40 px-3 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                        {{ __('Order') }}: {{ $countByType['order'] ?? 0 }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 dark:bg-amber-950/40 px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        {{ __('Logdown') }}: {{ $countByType['logdown'] ?? 0 }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/40 px-3 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                        {{ __('Upgrade') }}: {{ $countByType['upgrade'] ?? 0 }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/40 px-3 py-1 text-xs font-medium text-red-700 dark:text-red-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                        {{ __('Dismantle') }}: {{ $countByType['dismantle'] ?? 0 }}
                    </span>
                </div>
            </div>

            {{-- ===================== MAIN GRID ===================== --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- ============ KIRI: TIMELINE ============ --}}
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 dark:bg-slate-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Riwayat Aktivitas') }}</h3>

                        @if ($timeline->isEmpty())
                            <div class="rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 p-6 text-center">
                                <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('Belum ada aktivitas tercatat untuk client ini.') }}</p>
                            </div>
                        @else
                            <div class="relative">
                                {{-- Garis vertikal timeline --}}
                                <div class="absolute left-3 top-2 bottom-2 w-px bg-gray-200 dark:bg-slate-700" aria-hidden="true"></div>

                                <ol class="space-y-4">
                                    @foreach ($timeline as $idx => $event)
                                        @php($c = $spotlight[$event['color']] ?? $spotlight['gray'])
                                        <li class="relative pl-10">
                                            {{-- Dot penanda --}}
                                            <span class="absolute left-0 top-2 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white dark:ring-slate-800 {{ $c['dot'] }} text-white shadow-sm">
                                                @switch($event['icon'])
                                                    @case('plus')
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                                        @break
                                                    @case('arrow-up')
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                                        @break
                                                    @case('check-circle')
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                                        @break
                                                    @case('x-circle')
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                        @break
                                                    @case('bolt')
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                                                        @break
                                                    @default
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                                                @endswitch
                                            </span>

                                            <div class="rounded-lg border {{ $c['border'] }} {{ $c['bg'] }} p-4 dark:bg-slate-900/40">
                                                <div class="flex flex-wrap items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex items-center rounded-full bg-white/80 dark:bg-slate-800/60 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ $c['text'] }}">
                                                                {{ $typeLabel[$event['type']] ?? ucfirst($event['type']) }}
                                                            </span>
                                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                                                                {{ $event['title'] }}
                                                            </h4>
                                                        </div>
                                                        @if ($event['description'])
                                                            <p class="mt-1 text-xs text-gray-700 dark:text-slate-300">{{ $event['description'] }}</p>
                                                        @endif
                                                    </div>
                                                    <time class="shrink-0 text-xs font-medium text-gray-500 dark:text-slate-400 whitespace-nowrap" datetime="{{ $event['date']->toIso8601String() }}">
                                                        {{ $event['date']->format('d/m/Y H:i') }}
                                                    </time>
                                                </div>

                                                {{-- Detail per type --}}
                                                @if ($event['type'] === 'order' && $event['subtype'] === 'order_created')
                                                    <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-3">
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('No. Order') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">
                                                                @if (isset($event['meta']['order_id']))
                                                                    <a href="{{ route('orders.show', $event['meta']['order_id']) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                                        {{ $event['meta']['order_number'] }}
                                                                    </a>
                                                                @else
                                                                    {{ $event['meta']['order_number'] }}
                                                                @endif
                                                            </dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Tipe') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">{{ $event['meta']['order_type'] === 'upgrade' ? __('Upgrade') : __('Order Baru') }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Order Asal') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">
                                                                @if (!empty($event['meta']['parent_order_id']))
                                                                    <a href="{{ route('orders.show', $event['meta']['parent_order_id']) }}" class="text-blue-600 dark:text-blue-400 hover:underline">#{{ $event['meta']['parent_order_id'] }}</a>
                                                                @else
                                                                    —
                                                                @endif
                                                            </dd>
                                                        </div>
                                                    </dl>
                                                @elseif ($event['type'] === 'order' && $event['subtype'] === 'status_change')
                                                    <div class="mt-3 text-xs text-gray-600 dark:text-slate-300">
                                                        <span class="font-mono">{{ $event['meta']['order_number'] }}</span>
                                                    </div>

                                                    {{-- Daftar dokumen (multi) untuk tahap ini --}}
                                                    @if (!empty($event['meta']['documents']) && count($event['meta']['documents']) > 0)
                                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                                            @foreach ($event['meta']['documents'] as $doc)
                                                                <a href="{{ $doc['preview_url'] }}" target="_blank" rel="noopener"
                                                                    class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-blue-600 hover:underline dark:border-slate-700 dark:bg-slate-900/50 dark:text-blue-400"
                                                                    title="{{ $doc['name'] ?? __('Dokumen') }}">
                                                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                                    <span class="max-w-[12rem] truncate">{{ $doc['name'] ?? __('Dokumen') }}</span>
                                                                    @if (!empty($doc['ext']))
                                                                        <span class="ml-1 text-[10px] uppercase text-gray-400 dark:text-slate-500">{{ $doc['ext'] }}</span>
                                                                    @endif
                                                                    @if (!empty($doc['size_mb']))
                                                                        <span class="ml-1 text-[10px] text-gray-400 dark:text-slate-500">{{ $doc['size_mb'] }} MB</span>
                                                                    @endif
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @elseif ($event['type'] === 'upgrade')
                                                    <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-3">
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Order Upgrade') }}</dt>
                                                            <dd>
                                                                @if (!empty($event['meta']['order_id']))
                                                                    <a href="{{ route('orders.show', $event['meta']['order_id']) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                                        {{ $event['meta']['order_number'] }}
                                                                    </a>
                                                                @endif
                                                            </dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Layanan Sebelumnya') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">{{ $event['meta']['old_bandwidth'] ?? '—' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('MRC Lama') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100"><x-rupiah :value="$event['meta']['old_mrc'] ?? null" /></dd>
                                                        </div>
                                                    </dl>
                                                @elseif ($event['type'] === 'logdown')
                                                    <dl class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-4">
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Vendor') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">{{ $event['meta']['vendor'] ?? '—' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Mulai') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">{{ optional($event['meta']['down_at'])->format('d/m/Y H:i') }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Pulih') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">{{ optional($event['meta']['up_at'])?->format('d/m/Y H:i') ?? '—' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-gray-500 dark:text-slate-400">{{ __('Durasi') }}</dt>
                                                            <dd class="font-medium text-gray-900 dark:text-slate-100">{{ $event['meta']['duration'] ?? '—' }}</dd>
                                                        </div>
                                                    </dl>
                                                    @if (!empty($event['meta']['reason']))
                                                        <p class="mt-2 text-xs text-gray-600 dark:text-slate-300"><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Alasan') }}:</span> {{ $event['meta']['reason'] }}</p>
                                                    @endif
                                                    @if (!empty($event['meta']['action']))
                                                        <p class="mt-1 text-xs text-gray-600 dark:text-slate-300"><span class="font-medium text-gray-500 dark:text-slate-400">{{ __('Tindakan') }}:</span> {{ $event['meta']['action'] }}</p>
                                                    @endif
                                                @elseif ($event['type'] === 'dismantle')
                                                    <div class="mt-3 text-xs text-gray-600 dark:text-slate-300">
                                                        <span class="font-mono">{{ $event['meta']['order_number'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ============ KANAN: RINGKASAN ============ --}}
                <div class="space-y-6">
                    {{-- Ringkasan client --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-5 dark:bg-slate-800">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">{{ __('Ringkasan Client') }}</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-gray-500 dark:text-slate-400">{{ __('Status') }}</dt>
                                <dd class="text-right text-gray-900 dark:text-slate-100">{{ $client->status === 'active' ? __('Aktif') : __('Tidak Aktif') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-gray-500 dark:text-slate-400">{{ __('Alamat') }}</dt>
                                <dd class="text-right text-gray-900 dark:text-slate-100">{{ $client->address ?: '—' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-gray-500 dark:text-slate-400">{{ __('Total Order') }}</dt>
                                <dd class="text-right text-gray-900 dark:text-slate-100">{{ $orders->count() }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-gray-500 dark:text-slate-400">{{ __('Total Logdown') }}</dt>
                                <dd class="text-right text-gray-900 dark:text-slate-100">{{ $logdowns->count() }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Layanan aktif saat ini (mirip panel-detail order{id}) --}}
                    @if ($latestActiveOrder)
                        <div class="bg-white shadow-sm sm:rounded-lg p-5 dark:bg-slate-800">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">{{ __('Layanan Aktif Saat Ini') }}</h3>
                            <dl class="space-y-2 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-slate-400">{{ __('No. Order') }}</dt>
                                    <dd class="text-right">
                                        <a href="{{ route('orders.show', $latestActiveOrder) }}" class="font-medium text-blue-600 dark:text-blue-400 hover:underline">{{ $latestActiveOrder->display_number }}</a>
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-slate-400">{{ __('Provider') }}</dt>
                                    <dd class="text-right text-gray-900 dark:text-slate-100">{{ $latestActiveOrder->provider?->name ?? '—' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-slate-400">{{ __('Vendor') }}</dt>
                                    <dd class="text-right text-gray-900 dark:text-slate-100">{{ $latestActiveOrder->vendor?->name ?? '—' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-slate-400">{{ __('Paket') }}</dt>
                                    <dd class="text-right text-gray-900 dark:text-slate-100">{{ $latestActiveOrder->package?->name ?? $latestActiveOrder->package_name ?? '—' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-slate-400">{{ __('Bandwidth') }}</dt>
                                    <dd class="text-right text-gray-900 dark:text-slate-100">{{ $latestActiveOrder->bandwidth_label ?: '—' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-gray-500 dark:text-slate-400">{{ __('Status') }}</dt>
                                    <dd>
                                        @if ($latestActiveOrder->is_dismantled)
                                            <span class="inline-flex items-center rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                                                {{ __('Dismantled') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-950/40 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                                                {{ $statusService->title($latestActiveOrder->status) }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    {{-- Catatan: ini view-only --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs text-gray-600 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-300">
                        <p class="font-medium text-gray-700 dark:text-slate-200">{{ __('Tampilan hanya-baca') }}</p>
                        <p class="mt-1">{{ __('Halaman ini hanya menampilkan riwayat. Untuk tindakan edit/upgrade/dismantle/logdown gunakan modul masing-masing.') }}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>