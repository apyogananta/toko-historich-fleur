<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateShippingCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination'     => 'required|string|max:10',
            'weight'          => 'required|integer|min:1',
            'courier'         => 'required|string',
            'price'           => 'nullable|string|in:lowest,highest',
        ];
    }

    public function messages(): array
    {
        return [
            'destination.required' => 'Tujuan pengiriman (Kode Pos) wajib diisi.',
            'destination.max'      => 'Kode Pos tujuan maksimal 10 karakter.',
            'weight.required'      => 'Berat paket (gram) wajib diisi.',
            'weight.integer'       => 'Berat paket harus angka.',
            'weight.min'           => 'Berat paket minimal 1 gram.',
            'courier.required'     => 'Kurir wajib dipilih.',
            'price.in'             => 'Opsi filter harga tidak valid (hanya lowest/highest).',
        ];
    }
}

