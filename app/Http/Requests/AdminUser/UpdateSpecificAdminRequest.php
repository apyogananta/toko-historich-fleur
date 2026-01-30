<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecificAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Dapatkan admin yang akan diupdate dari route parameter
        // Pastikan nama parameter di route cocok (misal: {admin})
        $adminId = $this->route('admin') ? $this->route('admin')->id : null;

        return [
            'name' => 'required|string|max:50',
            'email' => [
                'required',
                'string',
                'email',
                // Pastikan email unik, kecuali untuk admin yang sedang diedit
                Rule::unique('admin_users', 'email')->ignore($adminId),
            ],
            // Password tidak diizinkan diupdate melalui request ini
            // Atau jika diizinkan, tambahkan validasi dan otorisasi ketat
            // 'password' => 'nullable|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama tidak boleh lebih dari 50 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar, gunakan email lain.',
        ];
    }
}
