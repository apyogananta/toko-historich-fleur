<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_name' => 'required|string|max:50',
            'phone_number'   => ['required', 'string', 'max:15'/*, 'regex:/^(\+62|62|0)8[1-9][0-9]{6,10}$/'*/],
            'address_line1'  => 'required|string|max:100',
            'address_line2'  => 'nullable|string|max:50',
            'province'       => 'required|string|max:50',
            'city'           => 'required|string|max:50',
            'postal_code'    => ['required', 'string', 'max:10'/*, 'numeric'*/],
            'is_default'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'phone_number.required'   => 'Nomor telepon wajib diisi.',
            'phone_number.max'        => 'Nomor telepon maksimal 15 karakter.',
            // 'phone_number.regex'      => 'Format nomor telepon Indonesia tidak valid.',
            'address_line1.required'  => 'Alamat baris 1 wajib diisi.',
            'province.required'       => 'Provinsi wajib diisi.',
            'city.required'           => 'Kota wajib diisi.',
            'postal_code.required'    => 'Kode pos wajib diisi.',
            // 'postal_code.numeric'     => 'Kode pos harus berupa angka.',
            'postal_code.max'         => 'Kode pos maksimal 10 karakter.',
             'is_default.boolean'      => 'Status default harus boolean (true/false).',
        ];
    }
}
