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


    public function rules()
    {
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
                // function ($attribute, $value, $fail) {
                //     if ($value instanceof \Illuminate\Http\UploadedFile) {
                //         if (!in_array($value->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                //             $fail('The logo must be an image file (jpg, jpeg, png, gif, webp).');
                //         }
                //     } elseif (is_string($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                //         $fail('The logo must be a valid URL or file upload.');
                //     }
                // }
            ],
            'image' => [
                'nullable',
                // function ($attribute, $value, $fail) {
                //     if ($value instanceof \Illuminate\Http\UploadedFile) {
                //         if (!in_array($value->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                //             $fail('The image must be an image file (jpg, jpeg, png, gif, webp).');
                //         }
                //     }
                // }
            ],
            'background_image' => [
                'nullable',
                // function ($attribute, $value, $fail) {
                //     if ($value instanceof \Illuminate\Http\UploadedFile) {
                //         if (!in_array($value->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                //             $fail('The background image must be an image file (jpg, jpeg, png, gif, webp).');
                //         }
                //     }
                // }
            ],
            'images' => 'nullable|array',
            'images.*' => [
                'nullable',
                // function ($attribute, $value, $fail) {
                //     if ($value instanceof \Illuminate\Http\UploadedFile) {
                //         if (!in_array($value->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                //             $fail('Each image must be an image file (jpg, jpeg, png, gif, webp).');
                //         }
                //     }
                // }
            ],
        ];
    }


    public function messages()
    {
        return [
            'id.required' => 'The business ID field is required.',
            'id.numeric' => 'The business ID must be a numeric value.',
            'id.exists' => 'The selected business ID is invalid.',
            'name.required' => 'The name field is required.',
            'name.string' => 'The name field must be a string.',
            'name.max' => 'The name field may not be greater than :max characters.',
            'about.string' => 'The about field must be a string.',
            'web_page.string' => 'The web page field must be a string.',
            'identifier_prefix.string' => 'The identifier prefix field must be a string.',
            'delete_read_notifications_after_30_days.required' => 'The delete_read_notifications_after_30_days field must be a required.',
            'business_start_day.required' => 'The business_start_day field must be a required.',
            'pin_code.string' => 'The pin code field must be a string.',
            'phone.string' => 'The phone field must be a string.',
            'email.email' => 'The email must be a valid email address.',
            'email.string' => 'The email field must be a string.',
            'email.unique' => 'The email has already been taken.',
            'email.exists' => 'The selected email is invalid.',
            'additional_information.string' => 'The additional information field must be a string.',
            'lat.required' => 'The latitude field is required.',
            'lat.string' => 'The latitude field must be a string.',
            'long.required' => 'The longitude field is required.',
            'long.string' => 'The longitude field must be a string.',
            'country.required' => 'The country field is required.',
            'country.string' => 'The country field must be a string.',
            'city.required' => 'The city field is required.',
            'city.string' => 'The city field must be a string.',
            'currency.required' => 'The currency field is required.',
            'currency.string' => 'The currency must be a string.',
            'postcode.string' => 'The postcode field must be a string.',
            'address_line_1.required' => 'The address line 1 field is required.',
            'address_line_1.string' => 'The address line 1 field must be a string.',
            'address_line_2.string' => 'The address line 2 field must be a string.',
            'logo.string' => 'The logo field must be a string.',
            'image.string' => 'The image field must be a string.',
            'images.array' => 'The images field must be an array.',
            'images.*.string' => 'Each image in the images field must be a string.',
        ];
    }
}
