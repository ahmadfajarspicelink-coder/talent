<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-200 leading-tight">
            {{ __('Partner') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Create form (R3.1) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-slate-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Tambah Partner') }}</h3>

                    <form method="POST" action="{{ route('partners.store') }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {{-- Name --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Nama') }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Type --}}
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Tipe') }}</label>
                                <select id="type" name="type"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="" disabled {{ old('type') ? '' : 'selected' }}>{{ __('Pilih tipe') }}</option>
                                    <option value="provider" {{ old('type') === 'provider' ? 'selected' : '' }}>{{ __('Provider') }}</option>
                                    <option value="vendor" {{ old('type') === 'vendor' ? 'selected' : '' }}>{{ __('Vendor') }}</option>
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Status') }}</label>
                                <input id="status" name="status" type="text" value="{{ old('status', 'active') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Address --}}
                            <div class="sm:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('Alamat') }}</label>
                                <input id="address" name="address" type="text" value="{{ old('address') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                @error('address')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- PIC --}}
                            <div>
                                <label for="pic" class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ __('PIC') }}</label>
                                <input id="pic" name="pic" type="text" value="{{ old('pic') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                @error('pic')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2.5 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white tracking-wide shadow-nova-sm hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Simpan') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Partner list table (R3.4) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-slate-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Daftar Partner') }}</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 dark:text-slate-400">
                                    <th class="px-3 py-2 font-medium">{{ __('Nama') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Tipe') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Alamat') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('PIC') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('Status') }}</th>
                                    <th class="px-3 py-2 font-medium text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @forelse ($partners as $partner)
                                    <tr x-data="{ editing: false }">
                                        {{-- Display row --}}
                                        <template x-if="!editing">
                                            <td class="px-3 py-2 align-top">{{ $partner->name }}</td>
                                        </template>
                                        <template x-if="!editing">
                                            <td class="px-3 py-2 align-top capitalize">{{ $partner->type }}</td>
                                        </template>
                                        <template x-if="!editing">
                                            <td class="px-3 py-2 align-top">{{ $partner->address }}</td>
                                        </template>
                                        <template x-if="!editing">
                                            <td class="px-3 py-2 align-top">{{ $partner->pic }}</td>
                                        </template>
                                        <template x-if="!editing">
                                            <td class="px-3 py-2 align-top">{{ $partner->status }}</td>
                                        </template>
                                        <template x-if="!editing">
                                            <td class="px-3 py-2 align-top text-right whitespace-nowrap">
                                                <button type="button" @click="editing = true"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 font-medium">{{ __('Edit') }}</button>
                                                <form method="POST" action="{{ route('partners.destroy', $partner) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('{{ __('Hapus partner ini?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="ml-3 text-red-600 dark:text-red-400 hover:text-red-900 font-medium">{{ __('Hapus') }}</button>
                                                </form>
                                            </td>
                                        </template>

                                        {{-- Edit row (R3.5) --}}
                                        <td class="px-3 py-2" colspan="6" x-show="editing" x-cloak>
                                            <form method="POST" action="{{ route('partners.update', $partner) }}"
                                                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
                                                @csrf
                                                @method('PUT')

                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Nama') }}</label>
                                                    <input name="name" type="text" value="{{ $partner->name }}"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm text-sm" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Tipe') }}</label>
                                                    <select name="type"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm text-sm">
                                                        <option value="provider" {{ $partner->type === 'provider' ? 'selected' : '' }}>{{ __('Provider') }}</option>
                                                        <option value="vendor" {{ $partner->type === 'vendor' ? 'selected' : '' }}>{{ __('Vendor') }}</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Alamat') }}</label>
                                                    <input name="address" type="text" value="{{ $partner->address }}"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm text-sm" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('PIC') }}</label>
                                                    <input name="pic" type="text" value="{{ $partner->pic }}"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm text-sm" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-slate-400">{{ __('Status') }}</label>
                                                    <input name="status" type="text" value="{{ $partner->status }}"
                                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 shadow-sm text-sm" />
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white tracking-wide hover:bg-blue-700 transition">{{ __('Update') }}</button>
                                                    <button type="button" @click="editing = false"
                                                        class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 dark:border-slate-600 rounded-md font-semibold text-xs text-gray-700 dark:text-slate-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-slate-800/50 transition">{{ __('Batal') }}</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-slate-400">
                                            {{ __('Belum ada partner.') }}
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
