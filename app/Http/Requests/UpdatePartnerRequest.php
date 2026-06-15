<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Akses ke Modul_Partner ditangani oleh middleware role (R2), jadi di sini
     * cukup mengizinkan request yang sudah lolos middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Nama dan tipe wajib; tipe dibatasi pada provider atau vendor (R3.2, R3.3).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:provider,vendor'],
            'address' => ['nullable', 'string', 'max:255'],
            'pic' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Pesan validasi field wajib dan batasan tipe Partner (R3.2, R3.3).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama partner wajib diisi.',
            'type.required' => 'Tipe partner wajib diisi.',
            'type.in' => 'Tipe partner harus provider atau vendor.',
        ];
    }
}
