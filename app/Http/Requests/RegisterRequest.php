<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'         => 'required|string|max:50',
            'email'        => 'required|string|unique:site_users,email',
            'password'     => 'required|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'name.required'         => 'Nama wajib diisi.',
            'name.max'              => 'Nama tidak boleh lebih dari 50 karakter.',
            'email.required'        => 'email wajib diisi.',
            'email.unique'          => 'email sudah terdaftar, gunakan email lain.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password harus minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ];
    }
}
