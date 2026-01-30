<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:50',
            'email' => [
                'required',
                'string',
                Rule::unique('admin_users', 'email')->ignore($this->user()->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'name.required'         => 'Nama wajib diisi.',
            'name.max'              => 'Nama tidak boleh lebih dari 50 karakter.',
            'email.required'        => 'email wajib diisi.',
            'email.unique'          => 'email sudah terdaftar, gunakan email lain.',
            'password.min'          => 'Password harus minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ];
    }
}

