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
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'category_id' => ['required', 'numeric', new ValidCourseCategory()],
            'created_by' => ['nullable', 'numeric', new ValidUser()],
            'is_free' => 'boolean',
            'status' => 'nullable|in:draft,published,archived',
            'url' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:100',
            'cover' => 'nullable|string|max:255',
            'preview_video' => 'nullable|string|max:255',
            'duration' => 'nullable|integer|min:0',
            'video_duration' => 'nullable|integer|min:0',
            'course_preview_description' => 'nullable|string|max:500',
            'course_status' => 'nullable|string|max:100',
            'status_start_date' => 'nullable|date',
            'status_end_date' => 'nullable|date|after_or_equal:status_start_date',
            'number_of_students' => 'nullable|integer|min:0',
            'course_view' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'is_lock_lessons_in_order' => 'nullable|boolean',
            'access_duration' => 'nullable|string|max:100',
            'access_device_type' => 'nullable|string|max:100',
            'certificate_info' => 'nullable|string|max:255',
            // Pricing-related rules
            'pricing' => 'nullable|in:is_one_time_purchase,price,sale_price,sale_end_date,enterprise_price,is_included_membership,is_affiliatable,point_of_a_course,price_info',

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
            'title.string' => 'The course title must be a string.',
            'title.max' => 'The course title may not exceed 255 characters.',

            'description.string' => 'The description must be a string.',

            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price must be at least 0.',

            'category_id.required' => 'Please select a course category.',
            'category_id.numeric' => 'Invalid course category selected.',

            'created_by.numeric' => 'Invalid user ID.',

            'is_free.boolean' => 'The is_free field must be true or false.',

            'status.in' => 'Status must be draft, published, or archived.',

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
