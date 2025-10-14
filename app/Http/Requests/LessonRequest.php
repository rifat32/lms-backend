<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use App\Rules\ValidLesson;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            "subtitle" => ['nullable'],
            "video_width" => ['nullable', 'string'],
            "required_progress" => ['nullable', 'string'],
            'preview_video_source_type' => ['nullable', 'required_if:content_type,' . Lesson::CONTENT_TYPES["VIDEO"], Rule::in(array_values(Lesson::PREVIEW_VIDEO_SOURCE_TYPE))],
            "preview_video_url" => ['nullable',],
            "preview_video_poster" => ['nullable',],
            "preview_video_embed" => ['nullable', 'string'],
            "pdf_read_completion_required" => ['boolean'],


            "section_ids" => ['present'],
            "section_ids.*" => ['required', 'numeric'],
            'title' => 'required|string|max:255',
            'content_type' => ['required', Rule::in(array_values(Lesson::CONTENT_TYPES))],
            'content_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:1',
            'is_preview' => 'nullable|boolean',
            'is_time_locked' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'unlock_day_after_purchase' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'content' => 'nullable|string',

            // files
            'files' => 'nullable|array',
            'files.*' => 'nullable',
            'materials' => 'nullable|array',
            'materials.*' => 'nullable'



        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['id'] = ['required', 'numeric', new ValidLesson()];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'Lesson title is required.',
            'title.string' => 'Lesson title must be a string.',
            'content_type.required' => 'Content type is required.',
            'content_type.in' => 'Content type must be one of: ' . implode(', ', array_values(Lesson::CONTENT_TYPES)),
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
