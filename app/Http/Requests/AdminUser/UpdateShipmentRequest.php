<?php

namespace App\Http\Requests\AdminUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validShipmentStatuses = [
            'pending',
            'shipped',
            'delivered',
        ];

        return [
            'status' => [
                'required',
                'string',
                Rule::in($validShipmentStatuses),
            ],
            'tracking_number' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status pengiriman wajib diisi.',
            'status.string' => 'Format status pengiriman tidak valid.',
            'status.in' => 'Status pengiriman yang dipilih tidak valid.',
            'tracking_number.string' => 'Format nomor pelacakan tidak valid.',
            'tracking_number.max' => 'Nomor pelacakan terlalu panjang.',
        ];
    }
}
