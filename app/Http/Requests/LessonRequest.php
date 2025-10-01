<?php

namespace App\Http\Requests;

use App\Rules\ValidCourse;
use App\Rules\ValidLesson;
use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'course_id' => ['required', 'numeric', new ValidCourse()],
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:video,text,file,quiz',
            'content_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'section_id' => ['required', 'integer', new ValidLesson()],

            // new fields
            'duration' => 'nullable|integer|min:1',
            'is_preview' => 'nullable|boolean',
            'is_time_locked' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'unlock_day_after_purchase' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'files' => 'nullable|array',        // if multiple
            'files.*' => 'file|mimes:jpg,png,pdf,docx,mp4|max:20480' // max 20MB per file
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
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

            'duration.integer' => 'Duration must be a number (minutes).',
            'is_preview.boolean' => 'Preview flag must be true/false.',
            'is_time_locked.boolean' => 'Time lock flag must be true/false.',
            'start_date.date' => 'Start date must be a valid date.',
            'start_time.date_format' => 'Start time must be in H:i format.',
            'unlock_day_after_purchase.integer' => 'Unlock days must be a number.',
            'files.array' => 'Files must be an array.',
            'files.*.file' => 'Each uploaded file must be a valid file.',
            'files.*.mimes' => 'Files must be of type: jpg, png, pdf, docx, mp4.',
            'files.*.max' => 'Files may not be greater than 20MB each.',

            'id.required' => 'Lesson ID is required for update.',
            'id.numeric' => 'Lesson ID must be numeric.',
        ];
    }
}
