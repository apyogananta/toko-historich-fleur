<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'images' => 'nullable|array',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
            'imagesToDelete' => 'nullable|array',
            'imagesToDelete.*' => 'required|integer|exists:product_images,id',
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
            'images.*.required' => 'File gambar baru wajib valid.',
            'images.*.image' => 'File baru harus berupa gambar.',
            'images.*.mimes' => 'Format gambar baru harus jpeg, jpg, png, atau webp.',
            'images.*.max' => 'Ukuran gambar baru maksimal 2MB.',
            'imagesToDelete.array' => 'Format data gambar yang dihapus tidak sesuai.',
            'imagesToDelete.*.integer' => 'ID gambar yang dihapus tidak valid.',
            'imagesToDelete.*.exists' => 'ID gambar yang dihapus tidak ditemukan.',
        ];
    }
}
