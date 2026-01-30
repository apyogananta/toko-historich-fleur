<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
         return [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
         return [
             'token.required' => 'Token reset password tidak ditemukan.',
             'email.required' => 'Alamat email wajib diisi.',
             'email.email'    => 'Format alamat email tidak valid.',
             'password.required' => 'Password baru wajib diisi.',
             'password.min' => 'Password baru minimal harus 8 karakter.',
             'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
         ];
    }
}
