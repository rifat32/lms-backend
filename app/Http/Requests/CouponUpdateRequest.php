<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coupon_id = $this->route('id');

        return [
            'course_id' => 'nullable|exists:courses,id',
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:coupons,code,' . $coupon_id,
            'discount_type' => 'sometimes|required|in:percentage,amount',
            'discount_amount' => 'sometimes|required|numeric|min:0',
            'min_total' => 'nullable|numeric|min:0',
            'max_total' => 'nullable|numeric|min:0',
            'redemptions' => 'nullable|integer|min:0',
            'coupon_start_date' => 'nullable|date',
            'coupon_end_date' => 'nullable|date|after_or_equal:coupon_start_date',
            'is_auto_apply' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
