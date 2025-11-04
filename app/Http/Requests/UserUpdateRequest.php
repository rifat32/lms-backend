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

    public function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id') ?? $this->input('id')]);
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
            'title' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->id), // ignore current user's email
            ],
            'profile_photo_path' => 'nullable',
            'phone' => 'nullable|string|max:11',
        ];

        return $rules;
    }
}
