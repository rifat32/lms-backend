<?php

namespace App\Http\Requests;

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
            'question_type' => 'required|in:mcq,true_false,short_answer',
            'points' => 'required|integer|min:1',
            'time_limit' => 'nullable|integer|min:0',
        ];

        if ($this->isMethod('post') || $this->isMethod('put')) {
            $rules['id'] = ['required', 'integer', new ValidQuestion()];
        }

        return $rules;
    }
}
