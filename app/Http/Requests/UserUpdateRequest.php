<?php

namespace App\Http\Requests;

use App\Rules\ValidUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $rules = [
            'id' => ['required', 'integer', new ValidUser()],
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->id), // ignore current user's email
            ],
            'profile_photo_path' => 'nullable',
            'phone' => 'required|string|max:11',
        ];

        return $rules;
    }
}
