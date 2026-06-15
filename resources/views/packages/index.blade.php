<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-nova-dark">{{ __('Paket Internet') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Tambah paket --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Tambah Paket') }}</h3>
                    <form method="POST" action="{{ route('packages.store') }}"
                        class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="name" :value="__('Nama Paket')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name')" placeholder="{{ __('mis. Dedicated 100 Mbps') }}" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                    </form>
                </div>
            </div>

            {{-- Daftar paket --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Daftar Paket') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-slate-400">
                                    <th class="px-3 py-2 font-medium">{{ __('ID') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Nama Paket') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Dipakai') }}</th>
                                    <th class="px-3 py-2 font-medium text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @forelse ($packages as $package)
                                    <tr x-data="{ editing: false }">
                                        <td class="px-3 py-3 align-middle text-gray-500 dark:text-slate-400">#{{ $package->id }}</td>
                                        <td class="px-3 py-3 align-middle" x-show="!editing">{{ $package->name }}</td>
                                        <td class="px-3 py-3 align-middle" x-show="!editing">
                                            <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-950/40 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                                                {{ $package->orders_count }} {{ __('order') }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 align-middle text-right whitespace-nowrap" x-show="!editing">
                                            <button type="button" @click="editing = true"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 font-medium">{{ __('Edit') }}</button>
                                            <form method="POST" action="{{ route('packages.destroy', $package) }}"
                                                class="inline" onsubmit="return confirm('{{ __('Hapus paket ini?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-3 text-red-600 dark:text-red-400 hover:text-red-900 font-medium">{{ __('Hapus') }}</button>
                                            </form>
                                        </td>

                                        {{-- Edit row --}}
                                        <td class="px-3 py-3" colspan="4" x-show="editing" x-cloak>
                                            <form method="POST" action="{{ route('packages.update', $package) }}"
                                                class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                                @csrf
                                                @method('PUT')
                                                <div class="flex-1">
                                                    <x-input-label :value="__('Nama Paket')" />
                                                    <x-text-input name="name" type="text" class="mt-1 block w-full"
                                                        :value="old('name', $package->name)" required />
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <x-primary-button>{{ __('Update') }}</x-primary-button>
                                                    <button type="button" @click="editing = false"
                                                        class="text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100 text-sm font-medium">{{ __('Batal') }}</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                            {{ __('Belum ada paket.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
