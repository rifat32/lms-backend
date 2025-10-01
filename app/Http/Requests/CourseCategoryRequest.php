<?php

namespace App\Http\Requests;

use App\Rules\ValidCourseCategory;
use Illuminate\Foundation\Http\FormRequest;

class CourseCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];

        if ($this->method() == 'PUT') {
            $rules['id'] = [
                'required',
                'integer',
                new ValidCourseCategory(),
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'id.required' => 'The course category ID is required.',
            'id.integer' => 'The course category ID must be an integer.',
        ];
    }
}
