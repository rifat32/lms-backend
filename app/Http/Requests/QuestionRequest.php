<?php

namespace App\Http\Requests;

use App\Models\Question;
use App\Rules\ValidQuestion;
use App\Rules\ValidQuiz;
use Illuminate\Foundation\Http\FormRequest;

class QuestionRequest extends FormRequest
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
            'quiz_id' => ['required', 'integer', new ValidQuiz()],
            'question_text' => 'required|string|max:255',
            'question_type' => ['required', 'in:' . implode(',', Question::TYPES)],
            'points' => 'required|integer|min:1',
            'time_limit' => 'nullable|integer|min:0',
            'is_required' => 'required|boolean',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = ['required', 'integer', new ValidQuestion()];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'question_type.required' => 'Please select a question type.',
            'question_type.in' => 'The selected question type is invalid. Allowed types are: ' . implode(', ', Question::TYPES),
        ];
    }
}
