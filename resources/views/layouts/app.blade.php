<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Inter (NovaSpark) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Apply saved theme before paint to avoid flash -->
        <script>
            (function () {
                try {
                    const t = localStorage.getItem('theme');
                    if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Scripts -->
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        @php
            $role = auth()->user()->role ?? '';
            $allowed = app(\App\Services\ModuleAccessPolicy::class)->allowedModules($role);
            $nav = [
                ['module' => null, 'route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard',
                    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['module' => 'order', 'route' => 'orders.index', 'pattern' => 'orders.*', 'label' => 'Order',
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['module' => 'partner', 'route' => 'partners.index', 'pattern' => 'partners.*', 'label' => 'Partner',
                    'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4 0M19 8a3 3 0 11-6 0 3 3 0 016 0z'],
                ['module' => 'package', 'route' => 'packages.index', 'pattern' => 'packages.*', 'label' => 'Paket Internet',
                    'icon' => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9'],
                ['module' => 'client', 'route' => 'clients.index', 'pattern' => 'clients.*', 'label' => 'Client',
                    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['module' => 'finance', 'route' => 'finance.orders', 'pattern' => 'finance.*', 'label' => 'Finance',
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                [
                    'module' => 'ticket',
                    'label' => 'Ticket',
                    'icon' => 'M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z',
                    'children' => [
                        ['module' => 'ticket', 'route' => 'logdown.index', 'pattern' => 'logdown.*', 'label' => 'Logdown',
                            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                    ],
                ],
                ['module' => 'user_management', 'route' => 'users.index', 'pattern' => 'users.*', 'label' => 'User Management',
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['module' => 'network', 'route' => 'network.index', 'pattern' => 'network.*', 'label' => 'Network',
                    'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01'],
            ];
        @endphp

        <div x-data="{ sidebarOpen: false }" class="min-h-screen">
            <!-- Mobile overlay -->
            <div x-show="sidebarOpen" x-cloak x-transition.opacity
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-nova-dark/50 lg:hidden"></div>

            <!-- Sidebar -->
            <aside x-cloak
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0">

                <!-- Brand -->
                <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-600 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 3L4 14h6l-1 7 9-11h-6l1-7z" />
                        </svg>
                    </span>
                    <span class="text-base font-bold tracking-tight text-nova-dark">{{ config('app.name', 'Laravel') }}</span>
                </div>

                <!-- Nav -->
                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                    @foreach ($nav as $item)
                        @continue($item['module'] !== null && ! in_array($item['module'], $allowed))

                        @if (! empty($item['children']))
                            {{-- Parent dengan sub-menu (collapsible) --}}
                            @php
                                $hasActiveChild = false;
                                foreach ($item['children'] as $child) {
                                    if (request()->routeIs($child['pattern'])) {
                                        $hasActiveChild = true;
                                        break;
                                    }
                                }
                            @endphp
                            <div x-data="{ open: {{ $hasActiveChild ? 'true' : 'false' }} }">
                                <button type="button" @click="open = ! open"
                                    class="nova-nav-item w-full {{ $hasActiveChild ? 'nova-nav-item-active' : '' }}">
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                    </svg>
                                    <span class="flex-1 text-left">{{ __($item['label']) }}</span>
                                    <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                        :class="open ? 'rotate-180' : ''"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                    </svg>
                                </button>
                                <div x-show="open" x-transition.duration.200ms
                                    class="ml-3 mt-1 space-y-1 border-l border-gray-200 pl-3">
                                    @foreach ($item['children'] as $child)
                                        @continue($child['module'] !== null && ! in_array($child['module'], $allowed))
                                        @php($childActive = request()->routeIs($child['pattern']))
                                        <a href="{{ route($child['route']) }}"
                                            class="nova-nav-item {{ $childActive ? 'nova-nav-item-active' : '' }}">
                                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $child['icon'] }}" />
                                            </svg>
                                            <span>{{ __($child['label']) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Item tunggal biasa --}}
                            @php($active = request()->routeIs($item['pattern']))
                            <a href="{{ route($item['route']) }}"
                                class="nova-nav-item {{ $active ? 'nova-nav-item-active' : '' }}">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                                </svg>
                                <span>{{ __($item['label']) }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>

                <!-- User -->
                <div class="border-t border-gray-200 p-3">
                    <div class="flex items-center gap-3 rounded-md px-2 py-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500 capitalize">{{ auth()->user()->role ?? '' }}</p>
                        </div>
                    </div>
                    <div class="mt-1 grid grid-cols-1 gap-1">
                        <a href="{{ route('profile.edit') }}" class="nova-nav-item py-2">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.27 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.27-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ __('Profile') }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nova-nav-item w-full py-2 text-left hover:bg-red-50 hover:text-red-600">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                <span>{{ __('Log Out') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Main column -->
            <div class="lg:pl-60">
                <!-- Top bar -->
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <div class="flex items-center gap-4">
                        <button type="button" @click="sidebarOpen = true"
                            class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            @isset($header)
                                {{ $header }}
                            @else
                                <h2 class="text-lg font-semibold text-nova-dark">{{ config('app.name') }}</h2>
                            @endisset
                        </div>
                    </div>

                    <!-- Clock + Theme toggle (pojok kanan) -->
                    <div class="flex items-center gap-3">
                        {{-- Live Clock WITA --}}
                        <div x-data="liveClock()" x-init="init()" x-cloak
                             class="flex items-center gap-0 rounded-full border border-gray-200 bg-white px-3 py-1.5 shadow-sm select-none">
                            <span class="text-sm font-bold tabular-nums" :class="isNight ? 'text-violet-600' : 'text-gray-900'" x-text="hours"></span>
                            <span class="text-sm font-bold text-gray-900">:</span>
                            <span class="text-sm font-bold tabular-nums text-gray-900" x-text="minutes"></span>
                            <span class="text-sm font-bold text-gray-900">:</span>
                            <span class="text-sm font-bold tabular-nums text-gray-900" x-text="seconds"></span>
                            <span class="ml-1.5 text-xs font-semibold text-gray-400">WITA</span>
                        </div>

                        {{-- Theme toggle --}}
                        <div x-data="{ dark: document.documentElement.classList.contains('dark'),
                            toggle() {
                                this.dark = ! this.dark;
                                document.documentElement.classList.toggle('dark', this.dark);
                                try { localStorage.setItem('theme', this.dark ? 'dark' : 'light'); } catch (e) {}
                            } }">
                            <button type="button" @click="toggle()"
                                class="flex h-9 w-9 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">
                                <!-- Moon (mode gelap) -->
                                <svg x-show="!dark" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                </svg>
                                <!-- Sun (mode terang) -->
                                <svg x-show="dark" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Page content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Toast notifikasi (pojok kanan atas). Menampilkan flash session
             status/success (hijau) & error (merah), auto-hilang + bisa ditutup. --}}
        @if (session('status') || session('success') || session('error'))
            <div class="pointer-events-none fixed top-4 right-4 z-50 w-full max-w-sm space-y-3 px-4 sm:px-0">
                @php($successMessage = session('status') ?: session('success'))

                @if ($successMessage)
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-init="setTimeout(() => show = false, 4500)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-6"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-6"
                        class="pointer-events-auto flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 shadow-nova-md"
                        role="status">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-green-700">{{ $successMessage }}</p>
                        <button type="button" @click="show = false"
                            class="ml-auto -mr-1 shrink-0 text-green-400 hover:text-green-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }" x-show="show" x-cloak
                        x-init="setTimeout(() => show = false, 5500)"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-6"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-6"
                        class="pointer-events-auto flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 shadow-nova-md"
                        role="alert">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                        <button type="button" @click="show = false"
                            class="ml-auto -mr-1 shrink-0 text-red-400 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        @endif

        @livewireScripts

        {{-- Live Clock WITA component --}}
        <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('liveClock', () => ({
                hours: '00',
                minutes: '00',
                seconds: '00',
                isNight: false,
                init() {
                    this.update();
                    setInterval(() => this.update(), 1000);
                },
                update() {
                    const now = new Date();
                    // Convert to WITA (UTC+8)
                    const utc = now.getTime() + now.getTimezoneOffset() * 60000;
                    const wita = new Date(utc + 8 * 3600000);
                    this.hours   = String(wita.getHours()).padStart(2, '0');
                    this.minutes = String(wita.getMinutes()).padStart(2, '0');
                    this.seconds = String(wita.getSeconds()).padStart(2, '0');
                    const h = wita.getHours();
                    this.isNight = (h < 6 || h >= 18);
                }
            }));
        });
        </script>

        @stack('scripts')
    </body>
</html>
