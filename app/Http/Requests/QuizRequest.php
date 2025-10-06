<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use App\Rules\ValidQuestion;
use App\Rules\ValidQuiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'time_unit' => ['nullable', Rule::in(array_values(Quiz::TIME_UNITS))],
            'style' => ['nullable', Rule::in(array_values(Quiz::STYLES))],
            'is_randomized' => 'nullable|boolean',
            'show_correct_answer' => 'nullable|boolean',
            'allow_retake_after_pass' => 'nullable|boolean',
            'max_attempts' => 'nullable|integer|min:1',
            'points_cut_after_retake' => 'nullable|integer|min:0|max:100',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'question_limit' => 'nullable|integer|min:0',
            'question_ids' => 'nullable|array',
            'question_ids.*' => ['required', 'integer', new ValidQuestion()],
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = ['required', 'integer', new ValidQuiz()];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'time_unit.in' => 'The time unit must be one of : ' . implode(',', array_values(Quiz::TIME_UNITS)),
            'style.in' => 'The style must be one of ' . implode(',', array_values(Quiz::STYLES)),
        ];
    }
}
