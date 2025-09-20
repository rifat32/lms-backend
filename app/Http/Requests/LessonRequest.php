<?php

namespace App\Http\Requests;

use App\Rules\ValidCourse;
use App\Rules\ValidLesson;
use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
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
            'course_id' => ['required', 'numeric', new ValidCourse()], // optional custom rule
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:video,text,file,quiz',
            'content_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            // For update, id is required
            $rules['id'] = ['required', 'numeric', new ValidLesson()];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'course_id.required' => 'Course ID is required.',
            'course_id.numeric' => 'Course ID must be a valid number.',
            'title.required' => 'Lesson title is required.',
            'title.string' => 'Lesson title must be a string.',
            'content_type.required' => 'Content type is required.',
            'content_type.in' => 'Content type must be one of: video, text, file, quiz.',
            'content_url.string' => 'Content URL must be a string.',
            'sort_order.integer' => 'Sort order must be a number.',
            'sort_order.min' => 'Sort order cannot be negative.',
            'id.required' => 'Lesson ID is required for update.',
            'id.numeric' => 'Lesson ID must be numeric.',
        ];
    }
}
