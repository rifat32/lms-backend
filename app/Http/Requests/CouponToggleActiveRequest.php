<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponToggleActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // controller will still validate permission; keep true so middleware/auth applies
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:coupons,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Coupon id is required',
            'id.exists' => 'Coupon not found',
        ];
    }
}
