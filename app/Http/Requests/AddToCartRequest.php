<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
         return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'qty'        => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists'   => 'Produk tidak ditemukan atau tidak valid.',
            'qty.required'        => 'Jumlah produk wajib diisi.',
            'qty.integer'         => 'Jumlah produk harus berupa angka.',
            'qty.min'             => 'Jumlah produk minimal 1.',
        ];
    }
}
