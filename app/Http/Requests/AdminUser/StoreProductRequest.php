<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:50',
            'color' => 'nullable|string|max:30',
            'category_id' => 'required|exists:categories,id',
            'original_price' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Nama produk wajib diisi.',
            'product_name.max' => 'Nama produk maksimal 50 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'original_price.required' => 'Harga asli wajib diisi.',
            'original_price.integer' => 'Harga asli harus angka.',
            'original_price.min' => 'Harga asli minimal 1.',
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Stok harus angka.',
            'stock.min' => 'Stok minimal 0.',
            'weight.required' => 'Berat wajib diisi.',
            'weight.integer' => 'Berat harus angka (gram).',
            'weight.min' => 'Berat minimal 1 gram.',
            'images.required' => 'Minimal satu gambar produk wajib diunggah.',
            'images.array' => 'Format data gambar tidak sesuai.',
            'images.min' => 'Minimal satu gambar produk wajib diunggah.',
            'images.*.required' => 'File gambar wajib diunggah.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
            'images.*.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}
