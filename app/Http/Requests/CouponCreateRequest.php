<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'nullable|exists:courses,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:coupons,code',
            'discount_type' => 'required|in:percentage,amount',
            'discount_amount' => 'required|numeric|min:0',
            'min_total' => 'nullable|numeric|min:0',
            'max_total' => 'nullable|numeric|min:0',
            'redemptions' => 'required|integer|min:0',
            'coupon_start_date' => 'required|date',
            'coupon_end_date' => 'required|date|after_or_equal:coupon_start_date',
            'is_auto_apply' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
    
}
