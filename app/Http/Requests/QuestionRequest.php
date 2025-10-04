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
    'question_text' => 'required|string|max:255',
    'question_type' => ['required', 'in:' . implode(',', Question::TYPES)],
    'points' => 'required|integer|min:1',
    'time_limit' => 'nullable|integer|min:0',
    'is_required' => 'required|boolean',

    'category_ids' => 'present|array',
    'category_ids.*' => 'integer|exists:question_categories,id',

    // Options array must be present
    'options' => 'required|array|min:1',

    // Each option field
    'options.*.id' => 'nullable|integer|exists:options,id', // optional ID for update
    'options.*.option_text' => 'nullable|string|max:255',
    'options.*.is_correct' => 'required|boolean',
    'options.*.explanation' => 'nullable|string',
    'options.*.image' => 'nullable', // Accept string URL or uploaded file, validate in controller
    'options.*.matching_pair_text' => 'nullable|string|max:255',
    'options.*.matching_pair_image' => 'nullable', // Accept string URL or uploaded file
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
