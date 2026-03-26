<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HandleSelfOrderPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:pay,continue'],
            'method' => ['nullable', 'string', 'in:online,cashier'],
            'token' => ['required', 'string', 'min:16', 'max:64'],
            'voucher_code' => ['nullable', 'string', 'max:80'],
            'use_points' => ['nullable', 'boolean'],
            'points_to_redeem' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'Aksi pembayaran tidak valid.',
            'method.in' => 'Metode pembayaran tidak valid.',
            'token.required' => 'Token pembayaran wajib diisi.',
        ];
    }
}
