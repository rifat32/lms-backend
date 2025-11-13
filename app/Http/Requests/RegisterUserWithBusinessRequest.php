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
        // list of user.* fields that should travel together
        $u = [
            'user.title',
            'user.first_name',
            'user.last_name',
            'user.email',
            'user.password',
        ];
        $list = implode(',', $u);

        // for each field, require it if any other user.* field is present
        // and, if you’re creating a NEW user (no user.id), require the set
        return [
            // ── USER INFORMATION ────────────────────────────────────────────────
            'user.id'         => ['nullable', 'integer', 'exists:users,id'],

            'user.title'      => ['nullable', 'string', 'max:255', "required_with:{$list}", 'required_without:user.id'],
            'user.first_name' => ['nullable', 'string', 'max:255', "required_with:{$list}", 'required_without:user.id'],
            'user.last_name'  => ['nullable', 'string', 'max:255', "required_with:{$list}", 'required_without:user.id'],
            'user.email'      => ['nullable', 'string', 'email', 'max:255', new UniqueUserEmail(), "required_with:{$list}", 'required_without:user.id'],
            'user.password'   => ['nullable', 'string', 'min:8', "required_with:{$list}", 'required_without:user.id'],

            // ── BUSINESS INFORMATION (your existing rules) ─────────────────────
            'business.name' => 'required|string|max:255',
            'business.about' => 'nullable|string',
            'business.web_page' => 'nullable|string',
            'business.pin_code' => 'nullable|string',
            'business.phone' => 'nullable|string',
            'business.email' => 'nullable|string|unique:businesses,email,' . ($this->business['id'] ?? 'NULL') . ',id',
            'business.additional_information' => 'nullable|string',
            'business.lat' => 'nullable|string',
            'business.long' => 'nullable|string',
            'business.currency' => 'nullable|string',
            'business.country' => 'required|string',
            'business.city' => 'required|string',
            'business.postcode' => 'nullable|string',
            'business.address_line_1' => 'required|string',
            'business.address_line_2' => 'nullable|string',
            'business.registration_date' => 'nullable|string',
            'business.logo' => 'nullable|string',
            'business.image' => 'nullable|string',
            'business.background_image' => 'nullable|string',
            'business.theme' => 'nullable|string',
            'business.images' => 'nullable|array',
            'business.images.*' => 'nullable|string',
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
