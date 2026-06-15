<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Harga negatif menolak penyimpanan Order sepenuhnya (R5.4): aturan
     * `min:0` pada komponen OTC/MRC membuat validasi gagal sebelum data
     * tersimpan, sehingga seluruh Order ditolak hingga harga diperbaiki.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_address' => ['nullable', 'string', 'max:1000'],
            'provider_id' => ['required', 'exists:partners,id'],
            'vendor_id' => ['nullable', 'exists:partners,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Client (nama) dan Provider wajib diisi; Vendor opsional.
            'client_name.required' => 'Nama client wajib diisi.',
            'provider_id.required' => 'Provider wajib diisi.',
        ];
    }
}
