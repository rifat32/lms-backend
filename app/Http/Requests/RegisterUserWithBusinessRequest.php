<?php

namespace App\Http\Requests;

use App\Rules\UniqueBusinessEmail;
use App\Rules\UniqueUserEmail;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserWithBusinessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // USER INFORMATION
            'user.title' => 'required|string|max:255',
            'user.first_name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.email' => ['required', 'string', 'email', 'max:255', new UniqueUserEmail()],
            'user.password' => 'nullable|string|min:8',

            // BUSINESS INFORMATION
            'business.name' => 'required|string|max:255',
            'business.email' => ['nullable', 'string', 'email', 'max:255', new UniqueBusinessEmail()],
            'business.phone' => 'nullable|string',
            'business.registration_date' => 'required|date|before_or_equal:today',
            'business.trail_end_date' => 'nullable|date',
            'business.about' => 'nullable|string',
            'business.web_page' => 'nullable|string',
            // 'business.identifier_prefix' => 'nullable|string',
            'business.address_line_1' => 'required|string',
            'business.country' => 'required|string',
            'business.city' => 'required|string',
            'business.postcode' => 'required|string',
            'business.currency' => 'nullable|string',
            'business.logo' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            // USER messages
            'user.title.required' => 'User title is required.',
            'user.title.string' => 'User title must be a string.',
            'user.title.max' => 'User title may not exceed 255 characters.',

            'user.first_name.required' => 'First name is required.',
            'user.first_name.string' => 'First name must be a string.',
            'user.first_name.max' => 'First name may not exceed 255 characters.',

            'user.last_name.required' => 'Last name is required.',
            'user.last_name.string' => 'Last name must be a string.',
            'user.last_name.max' => 'Last name may not exceed 255 characters.',

            'user.email.required' => 'User email is required.',
            'user.email.string' => 'User email must be a string.',
            'user.email.email' => 'User email must be a valid email address.',
            'user.email.max' => 'User email may not exceed 255 characters.',
            // UniqueUserEmail rule returns its own message, but keep a fallback:
            'user.email.unique' => 'This email is already registered.',

            'user.password.string' => 'Password must be a string.',
            'user.password.min' => 'Password must be at least 8 characters.',

            // BUSINESS messages
            'business.name.required' => 'Business name is required.',
            'business.name.string' => 'Business name must be a string.',
            'business.name.max' => 'Business name may not exceed 255 characters.',

            'business.email.string' => 'Business email must be a string.',
            'business.email.email' => 'Business email must be a valid email address.',
            'business.email.max' => 'Business email may not exceed 255 characters.',
            // UniqueBusinessEmail rule will return its message; fallback:
            'business.email.unique' => 'This business email is already in use.',

            'business.phone.string' => 'Business phone must be a string.',

            'business.registration_date.required' => 'Business registration date is required.',
            'business.registration_date.date' => 'Business registration date must be a valid date.',
            'business.registration_date.before_or_equal' => 'Business registration date cannot be in the future.',

            'business.trail_end_date.date' => 'Business trial end date must be a valid date.',

            'business.about.string' => 'Business about must be a string.',
            'business.web_page.string' => 'Business web page must be a string.',

            'business.address_line_1.required' => 'Business address line 1 is required.',
            'business.address_line_1.string' => 'Business address line 1 must be a string.',

            'business.country.required' => 'Business country is required.',
            'business.country.string' => 'Business country must be a string.',

            'business.city.required' => 'Business city is required.',
            'business.city.string' => 'Business city must be a string.',

            'business.postcode.required' => 'Business postcode is required.',
            'business.postcode.string' => 'Business postcode must be a string.',

            'business.currency.string' => 'Business currency must be a string.',
            'business.logo.string' => 'Business logo must be a string (path/filename).',

            // Generic fallback
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute must be a string.',
            'email' => 'The :attribute must be a valid email address.',
            'date' => 'The :attribute must be a valid date.',
        ];
    }
}
