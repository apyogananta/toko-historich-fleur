<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{

    public function rules()
    {
        return [
            'address_id' => 'required|exists:addresses,id',
            'items'      => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'address_id.required' => 'Alamat pengiriman wajib dipilih.',
            'address_id.exists'   => 'Alamat tidak ditemukan.',
            'items.required'      => 'Item pesanan tidak boleh kosong.',
            'items.array'         => 'Format item pesanan tidak valid.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.exists'   => 'Produk tidak ditemukan.',
            'items.*.qty.required'        => 'Jumlah produk wajib diisi.',
            'items.*.qty.integer'         => 'Jumlah produk harus berupa angka.',
            'items.*.qty.min'             => 'Jumlah produk minimal 1.',
        ];
    }
}
