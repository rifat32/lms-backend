<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'time_unit' => 'nullable|in:Hours,Minutes',
            'style' => 'nullable|in:pagination,all-in-one',
            'is_randomized' => 'nullable|boolean',
            'show_correct_answer' => 'nullable|boolean',
            'allow_retake_after_pass' => 'nullable|boolean',
            'max_attempts' => 'nullable|integer|min:1',
            'points_cut_after_retake' => 'nullable|integer|min:0|max:100',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'question_limit' => 'nullable|integer|min:0',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'integer|exists:questions,id',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|integer|exists:quizzes,id';
        }

        return $rules;
    }
}