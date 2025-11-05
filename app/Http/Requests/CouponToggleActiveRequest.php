<?php

namespace App\Http\Requests;

use App\Rules\ValidCoupon;
use Illuminate\Foundation\Http\FormRequest;

class CouponToggleActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // controller will still validate permission; keep true so middleware/auth applies
        return auth()->user()->hasAnyRole(['owner', 'admin', 'lecturer']);
    }

    public function rules(): array
    {
        return [
            'id' => 'required|numeric|exists:coupons,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Coupon id is required',
        ];
    }
}
