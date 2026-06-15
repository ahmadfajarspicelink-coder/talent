<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-200 leading-tight">
            {{ __('Manajemen Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Create form (R8.1) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-slate-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Tambah Pengguna') }}</h3>

                    <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {{-- Name --}}
                            <div>
                                <x-input-label for="name" :value="__('Nama')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            {{-- Email (email-unique => "email sudah dipakai" surfaces here) (R8.2) --}}
                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                    :value="old('email')" required />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            {{-- Password --}}
                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                    autocomplete="new-password" required />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            {{-- Role --}}
                            <div>
                                <x-input-label for="role" :value="__('Role')" />
                                <select id="role" name="role"
                                    class="mt-1 block w-full border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="staff" @selected(old('role', 'staff') === 'staff')>{{ __('Staff') }}</option>
                                    <option value="admin" @selected(old('role') === 'admin')>{{ __('Admin') }}</option>
                                </select>
                                <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center">
                            <x-primary-button>{{ __('Simpan Pengguna') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- User list (R8.3) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-slate-100">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-slate-100 mb-4">{{ __('Daftar Pengguna') }}</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                        {{ __('Nama') }}
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                        {{ __('Email') }}
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                        {{ __('Role') }}
                                    </th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                        {{ __('Aksi') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-slate-100">
                                            {{ $user->name }}
                                            @if ($user->id === auth()->id())
                                                <span class="ml-1 text-xs text-gray-400 dark:text-slate-500">{{ __('(Anda)') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-300">
                                            {{ $user->email }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($user->role === 'admin')
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-950/60 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:text-indigo-300">
                                                    {{ __('Admin') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-slate-700 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:text-slate-200">
                                                    {{ __('Staff') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-3">
                                                {{-- Change role (R8.4) --}}
                                                <form method="POST" action="{{ route('users.update', $user) }}"
                                                    class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <label for="role_{{ $user->id }}" class="sr-only">{{ __('Role') }}</label>
                                                    <select id="role_{{ $user->id }}" name="role"
                                                        class="border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm py-1">
                                                        <option value="staff" @selected($user->role === 'staff')>{{ __('Staff') }}</option>
                                                        <option value="admin" @selected($user->role === 'admin')>{{ __('Admin') }}</option>
                                                    </select>
                                                    <button type="submit"
                                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 font-medium">{{ __('Ubah Role') }}</button>
                                                </form>

                                                {{-- Delete (R8.5: self-delete rejected by controller) --}}
                                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                    class="inline"
                                                    onsubmit="return confirm('{{ __('Hapus pengguna ini?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="text-red-600 dark:text-red-400 hover:text-red-900 font-medium">{{ __('Hapus') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-slate-400" colspan="4">
                                            {{ __('Belum ada pengguna.') }}
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
