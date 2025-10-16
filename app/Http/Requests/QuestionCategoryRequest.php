<?php

namespace App\Http\Requests;

use App\Rules\ValidQuestionCategory;
use Illuminate\Foundation\Http\FormRequest;

class QuestionCategoryRequest extends FormRequest
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
            'slug' => 'required|string|max:255|unique:question_categories,slug,' . $this->id,

            'parent_question_category_id' => ['nullable', 'integer', new ValidQuestionCategory()],
        ];


        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['id'] = ['required', 'integer', new ValidQuestionCategory()];
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'The slug must be unique.',
        ];
    }
}
