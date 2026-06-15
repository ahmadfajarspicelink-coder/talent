{{--
    Form pembuatan Order baru (disederhanakan).

    Hanya mengumpulkan data dasar: Nama Client (wajib), Alamat Client,
    Provider (wajib), Vendor (opsional), dan Catatan. Detail layanan, harga,
    bandwidth, dan nomor PO/penawaran kini diisi bertahap pada Alur Pemesanan
    di halaman detail order setelah order dibuat.
--}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-slate-200 leading-tight">
            {{ __('Order Baru') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 sm:p-8 bg-white shadow sm:rounded-lg">
                <form method="POST" action="{{ route('orders.store') }}" class="space-y-6">
                    @csrf

                    {{-- Nama Client (wajib) --}}
                    <div>
                        <x-input-label for="client_name" :value="__('Nama Client')" />
                        <x-text-input id="client_name" name="client_name" type="text"
                            class="mt-1 block w-full" :value="old('client_name')"
                            placeholder="{{ __('Ketik nama client') }}" required autofocus />
                        <x-input-error :messages="$errors->get('client_name')" class="mt-2" />
                    </div>

                    {{-- Alamat Client --}}
                    <div>
                        <x-input-label for="client_address" :value="__('Alamat Client')" />
                        <textarea id="client_address" name="client_address" rows="2"
                            class="mt-1 block w-full border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="{{ __('Masukkan alamat client') }}">{{ old('client_address') }}</textarea>
                        <x-input-error :messages="$errors->get('client_address')" class="mt-2" />
                    </div>

                    {{-- Provider (wajib) & Vendor (opsional) --}}
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="provider_id" :value="__('Provider')" />
                            <select id="provider_id" name="provider_id"
                                class="mt-1 block w-full border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('Pilih provider') }}</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->id }}" @selected(old('provider_id') == $provider->id)>
                                        {{ $provider->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('provider_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="vendor_id" :value="__('Vendor')" />
                            <select id="vendor_id" name="vendor_id"
                                class="mt-1 block w-full border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('Pilih vendor (opsional)') }}</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>
                                        {{ $vendor->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <x-input-label for="note" :value="__('Catatan')" />
                        <textarea id="note" name="note" rows="2"
                            class="mt-1 block w-full border-gray-300 dark:border-slate-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            placeholder="{{ __('Catatan tambahan (opsional)') }}">{{ old('note') }}</textarea>
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('orders.index') }}"
                            class="text-sm text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100">{{ __('Batal') }}</a>
                        <x-primary-button>{{ __('Buat Order') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
