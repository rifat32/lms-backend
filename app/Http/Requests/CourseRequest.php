<?php

namespace App\Http\Requests;

use App\Models\Course;
use App\Rules\ValidCourse;
use App\Rules\ValidCourseCategory;
use App\Rules\ValidUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'cover' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lte:price',
            'price_start_date' => 'nullable|date',
            'price_end_date' => 'nullable|date|after_or_equal:price_start_date',
            'is_free' => 'nullable|boolean',

            'status' => ['required', Rule::in(array_values(Course::STATUS))],
            'preview_video_source_type' => ['nullable', Rule::in(array_values(Course::PREVIEW_VIDEO_SOURCE_TYPE))],

            'status_start_date' => 'nullable|date',
            'status_end_date' => 'nullable|date|after_or_equal:status_start_date',

            'url' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',

            'preview_video_url' => 'nullable|string|max:255|url',
            'preview_video_poster' => 'nullable|string|max:255',
            'preview_video_embed' => 'nullable|string',

            'duration' => 'nullable|string|max:50',
            'video_duration' => 'nullable|string|max:50',
            'course_preview_description' => 'nullable|string|max:500',

            'is_featured' => 'nullable|boolean',
            'is_lock_lessons_in_order' => 'nullable|boolean',

            'category_ids' => 'present|array',
            'category_ids.*' => ['numeric', new ValidCourseCategory()],
            'created_by' => ['nullable', 'numeric', new ValidUser()],
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['id'] = ['required', 'numeric', new ValidCourse()];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'The course title is required.',
            'title.string' => 'The course title must be a string.',
            'title.max' => 'The course title may not exceed 255 characters.',

            'description.string' => 'The description must be a string.',

            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price must be at least 0.',

            'category_id.required' => 'Please select a course category.',
            'category_id.numeric' => 'Invalid course category selected.',

            'created_by.numeric' => 'Invalid user ID.',

            'is_free.boolean' => 'The is_free field must be true or false.',

            'status.in' => 'Status must be one of: ' . implode(', ', array_values(Course::STATUS)) . '.',
            'preview_video_source_type.in' => 'Video source type must be one of: ' . implode(', ', array_values(Course::PREVIEW_VIDEO_SOURCE_TYPE)) . '.',

            'url.string' => 'The URL must be a string.',
            'level.string' => 'The level must be a string.',
            'cover.string' => 'The cover must be a string.',
            'preview_video.string' => 'The preview video must be a string.',

            'duration.integer' => 'Duration must be an integer.',
            'duration.min' => 'Duration cannot be negative.',

            'video_duration.integer' => 'Video duration must be an integer.',
            'video_duration.min' => 'Video duration cannot be negative.',

            'status_start_date.date' => 'Start date must be a valid date.',
            'status_end_date.date' => 'End date must be a valid date.',
            'status_end_date.after_or_equal' => 'End date must be after or equal to start date.',

            'number_of_students.integer' => 'Number of students must be an integer.',
            'number_of_students.min' => 'Number of students cannot be negative.',

            'is_featured.boolean' => 'The is_featured field must be true or false.',
            'is_lock_lessons_in_order.boolean' => 'The lock lessons field must be true or false.',

            'access_duration.string' => 'Access duration must be a string.',
            'access_device_type.string' => 'Access device type must be a string.',
            'certificate_info.string' => 'Certificate info must be a string.',

            'pricing.in' => 'The selected pricing type is invalid. Please choose a valid option.',

            'id.required' => 'Course ID is required for updates.',
            'id.numeric' => 'Course ID must be a number.',
        ];
    }
}
