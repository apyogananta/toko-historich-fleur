<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validOrderStatuses = [
            'cancelled',
            'awaiting_payment',
            'pending',
            'processed',
        ];

        return [
            'status' => [
                'required',
                Rule::in($validOrderStatuses),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan wajib diisi.',
            'status.in' => 'Status pesanan yang dipilih tidak valid.',
        ];
    }
}
