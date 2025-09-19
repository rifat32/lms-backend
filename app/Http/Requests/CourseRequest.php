<?php

namespace App\Http\Requests;

use App\Rules\ValidCourse;
use App\Rules\ValidCourseCategory;
use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
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
            'title' => 'required|string',
            'description' => 'required|string',
            'price' => 'nullable|numeric',
            'category_id' => ['required', 'numeric', new ValidCourseCategory()],
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            // Update: id is required and numeric
            $rules['id'] = ['required', 'numeric', new ValidCourse()];
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'The course title is required.',
            'category_id.required' => 'Please select a valid course category.',
        ];
    }
}
