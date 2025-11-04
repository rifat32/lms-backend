<?php

namespace App\Http\Requests;

use App\Rules\UniqueUserEmail;
use Illuminate\Foundation\Http\FormRequest;

class BusinessUpdateRequest extends FormRequest
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


    public function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id') ?? $this->input('id')]);
    }

    public function rules()
    {
        $id = $this->input('id');
        return [
            'id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => 'nullable|string|max:255', // or 'required' if mandatory
            'about' => 'nullable|string',
            'web_page' => 'nullable|url', // Added url validation
            'pin_code' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email|unique:businesses,email,' . ($this->id ?? 'NULL') . ',id', // Fixed
            'additional_information' => 'nullable|string',
            'lat' => 'nullable|string', // Should be numeric, not string
            'long' => 'nullable|string', // Should be numeric, not string
            'currency' => 'nullable|string|max:10',
            'country' => 'nullable|string',
            'city' => 'nullable|string',
            'postcode' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'theme' => 'nullable|string',

            // Image validations
            'logo' => [
                'nullable',
            ],
            'image' => [
                'nullable',
            ],
            'background_image' => [
                'nullable',
            ],
            'images' => 'nullable|array',
            'images.*' => [
                'nullable',
            ],
        ];
    }
}
