<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{

    public function rules()
    {
        return [
            'cartItems'                   => 'required|array|min:1',
            'cartItems.*.product_id'      => 'required|exists:products,id',
            'cartItems.*.qty'             => 'required|integer|min:1',
            'address_id'                  => 'required|exists:addresses,id',
            'shipping_option'             => 'required|array',
            'shipping_option.code'        => 'required|string',
            'shipping_option.service'     => 'required|string',
            'shipping_option.cost'        => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'cartItems.required'                   => 'Keranjang belanja kosong.',
            'address_id.required'                  => 'Alamat pengiriman tidak tersedia.',
            'shipping_option.required'             => 'Metode pengiriman tidak dipilih.',
            'cartItems.*.product_id.required'      => 'Produk wajib dipilih.',
            'cartItems.*.product_id.exists'        => 'Produk tidak ditemukan.',
            'cartItems.*.qty.required'             => 'Jumlah produk wajib diisi.',
            'cartItems.*.qty.integer'              => 'Jumlah produk harus berupa angka.',
            'cartItems.*.qty.min'                  => 'Jumlah produk minimal 1.',
        ];
    }
}
