<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
         return [
             'email.required' => 'Alamat email wajib diisi.',
             'email.email'    => 'Format alamat email tidak valid.',
         ];
    }
}
