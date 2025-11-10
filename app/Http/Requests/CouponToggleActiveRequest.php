<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CouponToggleActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller
        return true;
    }

    public function rules(): array
    {
        return [
            // No body validation needed since ID comes from route parameter
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
