<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => 'required|string|max:100|unique:categories,category_name',
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Nama kategori wajib diisi.',
            'category_name.max' => 'Nama kategori maksimal 100 karakter.',
            'category_name.unique' => 'Nama kategori sudah terdaftar.',
            'image.required' => 'Gambar kategori wajib diunggah.',
            'image.image' => 'File yang diunggah harus berupa gambar.',
            'image.max' => 'Ukuran gambar terlalu besar. Maksimal 2MB.',
            'image.mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
        ];
    }
}
