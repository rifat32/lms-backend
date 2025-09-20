<?php

namespace App\Http\Requests;

use App\Rules\ValidCourse;
use App\Rules\ValidCourseCategory;
use App\Rules\ValidUser;
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'category_id' => ['required', 'numeric', new ValidCourseCategory()],
            'lecturer_id' => ['nullable', 'numeric', new ValidUser()],
            'is_free' => 'boolean',
            'status' => 'in:draft,published,archived',
            'duration_days' => 'nullable|integer|min:0',
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
            'title.required' => 'Course title is required.',
            'description.required' => 'Course description is required.',
            'price.numeric' => 'Price must be a number.',
            'category_id.required' => 'Category is required.',
            'category_id.numeric' => 'Category must be a valid ID.',
            'lecturer_id.numeric' => 'Lecturer must be a valid ID.',
            'is_free.boolean' => 'Is Free must be true or false.',
            'status.in' => 'Status must be one of: draft, published, archived.',
            'duration_days.integer' => 'Duration must be a number of days.',
            'duration_days.min' => 'Duration must be at least 0 days.',
            'id.required' => 'Course ID is required for updates.',
            'id.numeric' => 'Course ID must be numeric.',
        ];
    }
}
