{{--
    Field input untuk menyelesaikan sebuah tahap (form "Tandai Selesai").
    Parameter: $order, $stage. Setiap input wajib diberi atribut data-req agar
    tombol "Tandai Selesai" hanya aktif bila semua field terisi.
--}}
@php($inputClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500')
@php($labelClass = 'block text-xs font-medium text-gray-600')
@php($maxDocs = 5)
@php($maxMb = 5)

@switch($stage)
    @case('Penawaran')
        <div>
            <label for="offer_number" class="{{ $labelClass }}">{{ __('Nomor Penawaran') }}</label>
            <input type="text" id="offer_number" name="offer_number" data-req
                value="{{ old('offer_number') }}" class="{{ $inputClass }}" />
            <x-input-error :messages="$errors->get('offer_number')" class="mt-1" />
        </div>
        @break

    @case('PO_Provider')
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="package_id" class="{{ $labelClass }}">{{ __('Jenis Layanan') }}</label>
                <select id="package_id" name="package_id" data-req class="{{ $inputClass }}">
                    <option value="">{{ __('— Pilih Jenis Layanan —') }}</option>
                    @foreach ($packages as $pkg)
                        <option value="{{ $pkg->id }}" @selected(old('package_id') == $pkg->id)>{{ $pkg->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('package_id')" class="mt-1" />
            </div>
            <div>
                <label for="po_provider_number" class="{{ $labelClass }}">{{ __('Nomor PO') }}</label>
                <input type="text" id="po_provider_number" name="po_provider_number" data-req
                    value="{{ old('po_provider_number') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('po_provider_number')" class="mt-1" />
            </div>
            <div>
                <label for="provider_otc" class="{{ $labelClass }}">{{ __('OTC Provider') }}</label>
                <input type="number" min="0" step="1" id="provider_otc" name="provider_otc" data-req
                    value="{{ old('provider_otc') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('provider_otc')" class="mt-1" />
            </div>
            <div>
                <label for="provider_mrc" class="{{ $labelClass }}">{{ __('MRC Provider') }}</label>
                <input type="number" min="0" step="1" id="provider_mrc" name="provider_mrc" data-req
                    value="{{ old('provider_mrc') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('provider_mrc')" class="mt-1" />
            </div>
            <div>
                <label for="bandwidth" class="{{ $labelClass }}">{{ __('Bandwidth (Mbps)') }}</label>
                <input type="number" min="0" step="1" id="bandwidth" name="bandwidth" data-req
                    value="{{ old('bandwidth') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('bandwidth')" class="mt-1" />
            </div>
        </div>
        @break

    @case('PO_Vendor')
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="po_vendor_number" class="{{ $labelClass }}">{{ __('Nomor PO') }}</label>
                <input type="text" id="po_vendor_number" name="po_vendor_number" data-req
                    value="{{ old('po_vendor_number') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('po_vendor_number')" class="mt-1" />
            </div>
            <div>
                <label for="vendor_otc" class="{{ $labelClass }}">{{ __('OTC Vendor') }}</label>
                <input type="number" min="0" step="1" id="vendor_otc" name="vendor_otc" data-req
                    value="{{ old('vendor_otc') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('vendor_otc')" class="mt-1" />
            </div>
            <div>
                <label for="vendor_mrc" class="{{ $labelClass }}">{{ __('MRC Vendor') }}</label>
                <input type="number" min="0" step="1" id="vendor_mrc" name="vendor_mrc" data-req
                    value="{{ old('vendor_mrc') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('vendor_mrc')" class="mt-1" />
            </div>
            <div>
                {{-- `vendor_bandwidth` disimpan terpisah dari `bandwidth` (PO_Provider)
                    agar nilai bandwidth PO_Provider tidak tertimpa. Lihat migrasi
                    2026_06_18_100000_add_vendor_bandwidth_to_orders_table. --}}
                <label for="vendor_bandwidth" class="{{ $labelClass }}">{{ __('Bandwidth (Mbps)') }}</label>
                <input type="number" min="0" step="1" id="vendor_bandwidth" name="vendor_bandwidth" data-req
                    value="{{ old('vendor_bandwidth', $order->vendor_bandwidth ?? $order->bandwidth) }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('vendor_bandwidth')" class="mt-1" />
            </div>
        </div>
        @break

    @case('BAA_BAST')
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="baa_number" class="{{ $labelClass }}">{{ __('Nomor BAA') }}</label>
                <input type="text" id="baa_number" name="baa_number" data-req
                    value="{{ old('baa_number') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('baa_number')" class="mt-1" />
            </div>
            <div>
                <label for="bast_number" class="{{ $labelClass }}">{{ __('Nomor BAST') }}</label>
                <input type="text" id="bast_number" name="bast_number" data-req
                    value="{{ old('bast_number') }}" class="{{ $inputClass }}" />
                <x-input-error :messages="$errors->get('bast_number')" class="mt-1" />
            </div>
        </div>
        @break

    @case('Client_Aktif')
        <div>
            <label for="contract_months" class="{{ $labelClass }}">{{ __('Durasi Kontrak (bulan, min 12)') }}</label>
            <input type="number" min="12" step="1" id="contract_months" name="contract_months" data-req
                value="{{ old('contract_months') }}" class="{{ $inputClass }} sm:w-48" />
            <x-input-error :messages="$errors->get('contract_months')" class="mt-1" />
        </div>
        @break

    @default
        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('Tidak ada data yang perlu diisi. Klik "Tandai Selesai" untuk melanjutkan.') }}</p>
@endswitch

{{-- Dokumen pendukung opsional (multi-upload) untuk tahap ini.
    Batas: maksimal {{ $maxDocs }} berkas, masing-masing ≤ {{ $maxMb }} MB.
    Berkas diunggah via OrderController::advanceStatus (saat "Tandai Selesai")
    atau via OrderDocumentController::store (saat "Upload dokumen" saja). --}}
<div class="mt-3">
    <label class="{{ $labelClass }}">{{ __('Dokumen pendukung (opsional, maks :maxDocs × :maxMb MB)', ['maxDocs' => $maxDocs, 'maxMb' => $maxMb]) }}</label>
    <input name="documents[]" type="file" multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
        data-multi-doc
        class="mt-1 text-xs text-gray-600 dark:text-slate-400 file:mr-2 file:rounded file:border-0 file:bg-gray-100 file:px-2 file:py-1 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200" />
    <x-input-error :messages="$errors->get('documents')" class="mt-1" />
    <x-input-error :messages="$errors->get('documents.*')" class="mt-1" />
</div>